<?php

namespace App\Jobs;

use App\Models\Monitor;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class DispatchCheckJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries = 1;
    public int $timeout = 60;

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $maxBatch = config('monitor.max_batch_size', 100);

        $dueMonitors = Monitor::where('is_paused', false)
            ->where('next_check_at', '<=', now())
            ->limit($maxBatch)
            ->get();

        if ($dueMonitors->isEmpty()) {
            Log::info("No monitors due for checking");
            return;
        }

        Log::info("Dispatching HTTP checks", ['count' => $dueMonitors->count()]);

        $jobs = $dueMonitors->map(fn ($monitor) => new HttpCheckJob($monitor))->toArray();

        Bus::batch($jobs)
            ->name('http-checks')
            ->then(function ($batch) {
                Log::info("HTTP check batch completed", ['total' => $batch->totalJobs()]);
            })
            ->catch(function ($batch, $exception) {
                Log::error("HTTP check batch failed", ['error' => $exception->getMessage()]);
            })
            ->onQueue('checks')
            ->dispatch();

        $sslInterval = config('monitor.ssl_check_interval_hours', 24);
        $sslMonitors = Monitor::where('is_paused', false)
            ->where('ssl_enabled', true)
            ->whereRaw("datetime('now', '-{$sslInterval} hours') > last_checked_at OR last_checked_at IS NULL")
            ->limit($maxBatch)
            ->get();

        if ($sslMonitors->isNotEmpty()) {
            Log::info("Dispatching SSL checks", ['count' => $sslMonitors->count()]);

            $sslJobs = $sslMonitors->map(fn ($monitor) => new SslCheckJob($monitor))->toArray();

            Bus::batch($sslJobs)
                ->name('ssl-checks')
                ->onQueue('ssl-checks')
                ->dispatch();
        }

        $domainInterval = config('monitor.domain_check_interval_hours', 48);
        $domainMonitors = Monitor::where('is_paused', false)
            ->where('domain_enabled', true)
            ->whereRaw("datetime('now', '-{$domainInterval} hours') > last_checked_at OR last_checked_at IS NULL")
            ->limit($maxBatch)
            ->get();

        if ($domainMonitors->isNotEmpty()) {
            Log::info("Dispatching domain checks", ['count' => $domainMonitors->count()]);

            $domainJobs = $domainMonitors->map(fn ($monitor) => new DomainCheckJob($monitor))->toArray();

            Bus::batch($domainJobs)
                ->name('domain-checks')
                ->onQueue('domain-checks')
                ->dispatch();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("DispatchCheckJobs failed", ['error' => $exception->getMessage()]);
    }
}
