<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\MonitorSsl;
use App\Models\MonitorDomain;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckService
{
    public function runHttpCheck(Monitor $monitor): array
    {
        if ($monitor->is_paused) {
            return ['status' => 'skipped', 'message' => 'Monitor is paused'];
        }

        $start = microtime(true);

        try {
            $response = Http::timeout(config('monitor.http_timeout', 10))
                ->withHeaders($monitor->headers ?? [])
                ->withBody($monitor->body ?? '', 'text/plain')
                ->withOptions([
                    'verify' => true,
                    'follow_redirects' => true,
                    'max_redirects' => 5,
                ])
                ->send($monitor->method, $monitor->url);

            $responseTimeMs = (int) ((microtime(true) - $start) * 1000);
            $httpCode = $response->status();
            $isUp = $this->evaluateResponse($monitor, $response, $httpCode);
            $status = $isUp ? 'up' : 'down';
            $message = $isUp ? null : "HTTP {$httpCode}";

        } catch (\Exception $e) {
            $responseTimeMs = (int) ((microtime(true) - $start) * 1000);
            $status = 'down';
            $httpCode = null;
            $message = $e->getMessage();
        }

        $previousStatus = $monitor->status;

        $monitor->recordCheck($status, $httpCode, $responseTimeMs, $message);

        if ($previousStatus !== 'unknown' && $previousStatus !== $status) {
            $this->handleStatusChange($monitor, $status, $previousStatus);
        }

        return [
            'status' => $status,
            'http_code' => $httpCode,
            'response_time_ms' => $responseTimeMs,
            'message' => $message,
        ];
    }

    public function runSslCheck(Monitor $monitor): array
    {
        if (!$monitor->ssl_enabled) {
            return ['status' => 'skipped'];
        }

        $domain = parse_url($monitor->url, PHP_URL_HOST);
        if (!$domain) {
            return ['status' => 'error', 'message' => 'Invalid URL'];
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
                return $this->recordSslError($monitor, $domain, "Connection failed: {$errstr}");
            }

            $cert = stream_context_get_params($stream)['options']['ssl']['peer_certificate'];
            fclose($stream);

            if (!$cert) {
                return $this->recordSslError($monitor, $domain, 'No certificate found');
            }

            $certInfo = openssl_x509_parse($cert);
            if (!$certInfo) {
                return $this->recordSslError($monitor, $domain, 'Failed to parse certificate');
            }

            $expiryDate = \Carbon\Carbon::createFromTimestamp($certInfo['validTo_time_t']);
            $daysLeft = max(0, now()->diffInDays($expiryDate, false));
            $issuer = $certInfo['issuer']['O'] ?? 'Unknown';

            MonitorSsl::create([
                'monitor_id' => $monitor->id,
                'domain' => $domain,
                'ssl_expiry_date' => $expiryDate,
                'days_left' => $daysLeft,
                'issuer' => $issuer,
                'serial_number' => $certInfo['serialNumberHex'] ?? null,
                'is_valid' => $daysLeft > 0,
                'checked_at' => now(),
            ]);

            return ['status' => 'ok', 'days_left' => $daysLeft, 'expiry_date' => $expiryDate->toDateString()];

        } catch (\Exception $e) {
            return $this->recordSslError($monitor, $domain, $e->getMessage());
        }
    }

    public function runDomainCheck(Monitor $monitor): array
    {
        if (!$monitor->domain_enabled) {
            return ['status' => 'skipped'];
        }

        $domain = parse_url($monitor->url, PHP_URL_HOST);
        if (!$domain) {
            return ['status' => 'error', 'message' => 'Invalid URL'];
        }

        $tld = $this->extractTld($domain);
        $whoisServer = $this->getWhoisServer($tld);

        if (!$whoisServer) {
            return $this->recordDomainError($monitor, $domain, "Unsupported TLD: {$tld}");
        }

        try {
            $result = $this->whoisQuery($whoisServer, $domain);
            $expiryDate = $this->parseExpiryDate($result);

            if (!$expiryDate) {
                return $this->recordDomainError($monitor, $domain, 'Could not parse expiry date');
            }

            $daysLeft = max(0, now()->diffInDays($expiryDate, false));
            $registrar = $this->parseRegistrar($result);

            MonitorDomain::create([
                'monitor_id' => $monitor->id,
                'domain' => $domain,
                'expiry_date' => $expiryDate,
                'days_left' => $daysLeft,
                'registrar' => $registrar,
                'checked_at' => now(),
            ]);

            return ['status' => 'ok', 'days_left' => $daysLeft, 'expiry_date' => $expiryDate->toDateString()];

        } catch (\Exception $e) {
            return $this->recordDomainError($monitor, $domain, $e->getMessage());
        }
    }

    public function runAllDueChecks(?int $teamId = null): array
    {
        $query = Monitor::where('is_paused', false)
            ->where('next_check_at', '<=', now());

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        $monitors = $query->limit(config('monitor.max_batch_size', 100))->get();
        $results = [];

        foreach ($monitors as $monitor) {
            $results[$monitor->id] = $this->runHttpCheck($monitor);
        }

        return $results;
    }

    private function evaluateResponse(Monitor $monitor, $response, int $httpCode): bool
    {
        if ($monitor->expected_status_code && $httpCode !== $monitor->expected_status_code) {
            return false;
        }
        if ($monitor->expected_keyword) {
            if (stripos($response->body(), $monitor->expected_keyword) === false) {
                return false;
            }
        }
        return $httpCode >= 200 && $httpCode < 400;
    }

    private function handleStatusChange(Monitor $monitor, string $newStatus, string $oldStatus): void
    {
        $shouldAlert = ($newStatus === 'down' && $monitor->alert_on_down)
            || ($newStatus === 'up' && $oldStatus === 'down' && $monitor->alert_on_up);

        if ($shouldAlert) {
            $recentAlert = NotificationLog::where('monitor_id', $monitor->id)
                ->where('sent_at', '>=', now()->subMinutes(5))
                ->exists();

            if (!$recentAlert) {
                NotificationLog::create([
                    'monitor_id' => $monitor->id,
                    'channel_type' => 'email',
                    'status' => 'sent',
                    'message' => "Monitor {$monitor->name} is now {$newStatus}",
                    'sent_at' => now(),
                ]);
            }
        }
    }

    private function recordSslError(Monitor $monitor, string $domain, string $error): array
    {
        MonitorSsl::create([
            'monitor_id' => $monitor->id,
            'domain' => $domain,
            'is_valid' => false,
            'error_message' => $error,
            'checked_at' => now(),
        ]);
        return ['status' => 'error', 'message' => $error];
    }

    private function recordDomainError(Monitor $monitor, string $domain, string $error): array
    {
        MonitorDomain::create([
            'monitor_id' => $monitor->id,
            'domain' => $domain,
            'error_message' => $error,
            'checked_at' => now(),
        ]);
        return ['status' => 'error', 'message' => $error];
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

    private function getWhoisServer(string $tld): ?string
    {
        $servers = [
            'com' => 'whois.verisign-grs.com', 'net' => 'whois.verisign-grs.com',
            'org' => 'whois.pir.org', 'info' => 'whois.afilias.net',
            'in' => 'whois.inregistry.net', 'co' => 'whois.nic.co',
            'io' => 'whois.nic.io', 'me' => 'whois.nic.me',
            'dev' => 'whois.nic.google', 'app' => 'whois.nic.google',
            'xyz' => 'whois.nic.xyz', 'tech' => 'whois.nic.tech',
            'cloud' => 'whois.nic.cloud', 'ai' => 'whois.nic.ai',
        ];
        return $servers[$tld] ?? null;
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
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $whoisResponse, $matches)) {
                $date = \Carbon\Carbon::parse(trim($matches[1]));
                if ($date->isValid() && $date->isFuture()) {
                    return $date;
                }
            }
        }
        return null;
    }

    private function parseRegistrar(string $whoisResponse): ?string
    {
        if (preg_match('/Registrar:\s*(.+)/i', $whoisResponse, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}
