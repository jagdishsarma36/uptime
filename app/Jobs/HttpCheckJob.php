<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Services\UptimeCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public int $backoff = 10;

    public function __construct(
        public Monitor $monitor
    ) {
        $this->onQueue('checks');
    }

    public function handle(): void
    {
        $this->monitor->load('team');

        if ($this->monitor->is_paused) {
            return;
        }

        if ($this->monitor->maintenanceWindows()->active()->exists()) {
            return;
        }

        $start = microtime(true);

        try {
            $response = Http::timeout(config('monitor.http_timeout', 10))
                ->withHeaders($this->monitor->headers ?? [])
                ->withBody($this->monitor->body ?? '', 'text/plain')
                ->withOptions([
                    'verify' => true,
                    'follow_redirects' => true,
                    'max_redirects' => 5,
                ])
                ->send($this->monitor->method, $this->monitor->url);

            $responseTimeMs = (int) ((microtime(true) - $start) * 1000);
            $httpCode = $response->status();

            $isUp = $this->evaluateResponse($response, $httpCode);

            $status = $isUp ? 'up' : 'down';
            $message = $isUp ? null : "HTTP {$httpCode} - Expected keyword not found";

            $previousStatus = $this->monitor->status;

            $this->monitor->recordCheck($status, $httpCode, $responseTimeMs, $message);

            if ($previousStatus !== 'unknown' && $previousStatus !== $status) {
                $this->monitor->team->auditLogs()->create([
                    'user_id' => null,
                    'event' => "monitor.{$status}",
                    'auditable_type' => Monitor::class,
                    'auditable_id' => $this->monitor->id,
                    'old_values' => ['status' => $previousStatus],
                    'new_values' => ['status' => $status],
                    'ip_address' => null,
                    'user_agent' => 'check-worker',
                    'created_at' => now(),
                ]);

                if ($this->shouldAlert($previousStatus, $status)) {
                    SendAlertJob::dispatch($this->monitor, $status, $previousStatus)
                        ->onQueue('alerts');
                }
            }

            Log::info("Monitor check completed", [
                'monitor_id' => $this->monitor->id,
                'status' => $status,
                'http_code' => $httpCode,
                'response_time_ms' => $responseTimeMs,
            ]);

        } catch (\Exception $e) {
            $responseTimeMs = (int) ((microtime(true) - $start) * 1000);

            $previousStatus = $this->monitor->status;

            $this->monitor->recordCheck('down', null, $responseTimeMs, $e->getMessage());

            if ($previousStatus !== 'unknown' && $previousStatus !== 'down') {
                if ($this->shouldAlert($previousStatus, 'down')) {
                    SendAlertJob::dispatch($this->monitor, 'down', $previousStatus)
                        ->onQueue('alerts');
                }
            }

            Log::warning("Monitor check failed", [
                'monitor_id' => $this->monitor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function evaluateResponse($response, int $httpCode): bool
    {
        if ($this->monitor->expected_status_code && $httpCode !== $this->monitor->expected_status_code) {
            return false;
        }

        if ($this->monitor->expected_keyword) {
            $body = $response->body();
            if (stripos($body, $this->monitor->expected_keyword) === false) {
                return false;
            }
        }

        return $httpCode >= 200 && $httpCode < 400;
    }

    private function shouldAlert(string $fromStatus, string $toStatus): bool
    {
        if ($toStatus === 'down' && $this->monitor->alert_on_down) {
            return true;
        }

        if ($toStatus === 'up' && $fromStatus === 'down' && $this->monitor->alert_on_up) {
            return true;
        }

        return false;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("HttpCheckJob failed permanently", [
            'monitor_id' => $this->monitor->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
