<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorSsl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SslCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 15;

    public function __construct(
        public Monitor $monitor
    ) {
        $this->onQueue('ssl-checks');
    }

    public function handle(): void
    {
        if (!$this->monitor->ssl_enabled) {
            return;
        }

        $domain = parse_url($this->monitor->url, PHP_URL_HOST);

        if (!$domain) {
            return;
        }

        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $stream = @stream_socket_client(
                "ssl://{$domain}:443",
                $errno,
                $errstr,
                5,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (!$stream) {
                $this->recordError($domain, "SSL connection failed: {$errstr}");
                return;
            }

            $cert = stream_context_get_params($stream)['options']['ssl']['peer_certificate'];
            fclose($stream);

            if (!$cert) {
                $this->recordError($domain, 'No certificate found');
                return;
            }

            $certInfo = openssl_x509_parse($cert);

            if (!$certInfo) {
                $this->recordError($domain, 'Failed to parse certificate');
                return;
            }

            $expiryDate = \Carbon\Carbon::createFromTimestamp($certInfo['validTo_time_t']);
            $daysLeft = max(0, now()->diffInDays($expiryDate, false));
            $issuer = $certInfo['issuer']['O'] ?? 'Unknown';

            MonitorSsl::create([
                'monitor_id' => $this->monitor->id,
                'domain' => $domain,
                'ssl_expiry_date' => $expiryDate,
                'days_left' => $daysLeft,
                'issuer' => $issuer,
                'serial_number' => $certInfo['serialNumberHex'] ?? null,
                'is_valid' => $daysLeft > 0,
                'checked_at' => now(),
            ]);

            if ($daysLeft <= $this->monitor->ssl_alert_threshold_days && $this->monitor->alert_on_ssl_expiry) {
                SendAlertJob::dispatch($this->monitor, 'ssl_expiry', null, [
                    'domain' => $domain,
                    'days_left' => $daysLeft,
                    'expiry_date' => $expiryDate->toDateString(),
                ])->onQueue('alerts');
            }

            Log::info("SSL check completed", [
                'monitor_id' => $this->monitor->id,
                'domain' => $domain,
                'days_left' => $daysLeft,
            ]);

        } catch (\Exception $e) {
            $this->recordError($domain, $e->getMessage());
        }
    }

    private function recordError(string $domain, string $error): void
    {
        MonitorSsl::create([
            'monitor_id' => $this->monitor->id,
            'domain' => $domain,
            'is_valid' => false,
            'error_message' => $error,
            'checked_at' => now(),
        ]);

        Log::warning("SSL check failed", [
            'monitor_id' => $this->monitor->id,
            'domain' => $domain,
            'error' => $error,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SslCheckJob failed permanently", [
            'monitor_id' => $this->monitor->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
