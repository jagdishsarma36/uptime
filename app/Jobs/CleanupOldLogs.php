<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorDomain;
use App\Models\MonitorSsl;
use App\Models\CheckResult;
use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupOldLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function handle(): void
    {
        $retentionDays = config('monitor.retention_days', 60);

        $cutoff = now()->subDays($retentionDays);

        Log::info("Starting cleanup", ['retention_days' => $retentionDays, 'cutoff' => $cutoff->toDateTimeString()]);

        $deletedLogs = CheckResult::where('checked_at', '<', $cutoff)->delete();
        Log::info("Deleted old check_results", ['count' => $deletedLogs]);

        $deletedSsl = MonitorSsl::where('checked_at', '<', $cutoff)->delete();
        Log::info("Deleted old monitor_ssl", ['count' => $deletedSsl]);

        $deletedDomain = MonitorDomain::where('checked_at', '<', $cutoff)->delete();
        Log::info("Deleted old monitor_domain", ['count' => $deletedDomain]);

        $deletedNotifications = NotificationLog::where('sent_at', '<', $cutoff)->delete();
        Log::info("Deleted old notification_log", ['count' => $deletedNotifications]);

        $deletedIncidents = DB::table('incident_updates')
            ->where('created_at', '<', $cutoff)
            ->delete();
        Log::info("Deleted old incident_updates", ['count' => $deletedIncidents]);

        Log::info("Cleanup completed");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("CleanupOldLogs failed", ['error' => $exception->getMessage()]);
    }
}
