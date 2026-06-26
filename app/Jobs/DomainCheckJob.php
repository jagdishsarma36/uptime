<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorDomain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DomainCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 15;

    private const WHOIS_SERVERS = [
        'com' => 'whois.verisign-grs.com',
        'net' => 'whois.verisign-grs.com',
        'org' => 'whois.pir.org',
        'info' => 'whois.afilias.net',
        'biz' => 'whois.biz',
        'in' => 'whois.inregistry.net',
        'co' => 'whois.nic.co',
        'io' => 'whois.nic.io',
        'me' => 'whois.nic.me',
        'dev' => 'whois.nic.google',
        'app' => 'whois.nic.google',
        'xyz' => 'whois.nic.xyz',
        'tech' => 'whois.nic.tech',
        'cloud' => 'whois.nic.cloud',
        'ai' => 'whois.nic.ai',
        'co.uk' => 'whois.nic.uk',
        'de' => 'whois.denic.de',
        'fr' => 'whois.nic.fr',
        'nl' => 'whois.sidn.nl',
        'eu' => 'whois.eu',
        'au' => 'whois.auda.org.au',
        'ca' => 'whois.cira.ca',
        'jp' => 'whois.jprs.jp',
        'br' => 'whois.registro.br',
    ];

    public function __construct(
        public Monitor $monitor
    ) {
        $this->onQueue('domain-checks');
    }

    public function handle(): void
    {
        if (!$this->monitor->domain_enabled) {
            return;
        }

        $domain = parse_url($this->monitor->url, PHP_URL_HOST);

        if (!$domain) {
            return;
        }

        $tld = $this->extractTld($domain);
        $whoisServer = self::WHOIS_SERVERS[$tld] ?? null;

        if (!$whoisServer) {
            $this->recordError($domain, "Unsupported TLD: {$tld}");
            return;
        }

        try {
            $result = $this->whoisQuery($whoisServer, $domain);

            $expiryDate = $this->parseExpiryDate($result);

            if (!$expiryDate) {
                $this->recordError($domain, 'Could not parse expiry date from WHOIS response');
                return;
            }

            $daysLeft = max(0, now()->diffInDays($expiryDate, false));
            $registrar = $this->parseRegistrar($result);

            MonitorDomain::create([
                'monitor_id' => $this->monitor->id,
                'domain' => $domain,
                'expiry_date' => $expiryDate,
                'days_left' => $daysLeft,
                'registrar' => $registrar,
                'checked_at' => now(),
            ]);

            if ($daysLeft <= $this->monitor->domain_alert_threshold_days && $this->monitor->alert_on_domain_expiry) {
                SendAlertJob::dispatch($this->monitor, 'domain_expiry', null, [
                    'domain' => $domain,
                    'days_left' => $daysLeft,
                    'expiry_date' => $expiryDate->toDateString(),
                ])->onQueue('alerts');
            }

            Log::info("Domain check completed", [
                'monitor_id' => $this->monitor->id,
                'domain' => $domain,
                'days_left' => $daysLeft,
            ]);

        } catch (\Exception $e) {
            $this->recordError($domain, $e->getMessage());
        }
    }

    private function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);
        $count = count($parts);

        if ($count >= 3) {
            $twoPart = strtolower($parts[$count - 2] . '.' . $parts[$count - 1]);
            if (in_array($twoPart, ['co.uk', 'com.au', 'co.nz'])) {
                return $twoPart;
            }
        }

        return strtolower($parts[$count - 1]);
    }

    private function whoisQuery(string $server, string $domain): string
    {
        $socket = @fsockopen($server, 43, $errno, $errstr, 8);

        if (!$socket) {
            throw new \RuntimeException("Could not connect to WHOIS server: {$errstr}");
        }

        fwrite($socket, "{$domain}\r\n");
        $response = '';

        while (!feof($socket)) {
            $response .= fgets($socket, 4096);
        }

        fclose($socket);

        return $response;
    }

    private function parseExpiryDate(string $whoisResponse): ?\Carbon\Carbon
    {
        $patterns = [
            '/Registry Expiry Date:\s*(.+)/i',
            '/Expiration Date:\s*(.+)/i',
            '/Domain Expiration Date:\s*(.+)/i',
            '/expires:\s*(.+)/i',
            '/Valid Until:\s*(.+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $whoisResponse, $matches)) {
                $dateStr = trim($matches[1]);
                $date = \Carbon\Carbon::parse($dateStr);

                if ($date->isValid() && $date->isFuture()) {
                    return $date;
                }
            }
        }

        return null;
    }

    private function parseRegistrar(string $whoisResponse): ?string
    {
        $patterns = [
            '/Registrar:\s*(.+)/i',
            '/Sponsoring Organisation:\s*(.+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $whoisResponse, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    private function recordError(string $domain, string $error): void
    {
        MonitorDomain::create([
            'monitor_id' => $this->monitor->id,
            'domain' => $domain,
            'error_message' => $error,
            'checked_at' => now(),
        ]);

        Log::warning("Domain check failed", [
            'monitor_id' => $this->monitor->id,
            'domain' => $domain,
            'error' => $error,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("DomainCheckJob failed permanently", [
            'monitor_id' => $this->monitor->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
