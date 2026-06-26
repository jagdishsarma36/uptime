<?php

namespace App\Services;

use App\Models\CheckResult;
use App\Models\Monitor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UptimeCalculator
{
    public function calculateUptime(Monitor $monitor, int $hours = 24): float
    {
        $since = now()->subHours($hours);

        $totalChecks = $monitor->checkResults()
            ->where('checked_at', '>=', $since)
            ->count();

        if ($totalChecks === 0) {
            return 100.0;
        }

        $upChecks = $monitor->checkResults()
            ->where('checked_at', '>=', $since)
            ->where('status', 'up')
            ->count();

        return round(($upChecks / $totalChecks) * 100, 2);
    }

    public function calculateTimeWeightedUptime(Monitor $monitor, int $hours = 24): float
    {
        $since = now()->subHours($hours);

        $results = $monitor->checkResults()
            ->where('checked_at', '>=', $since)
            ->orderBy('checked_at')
            ->get();

        if ($results->isEmpty()) {
            return 100.0;
        }

        $totalSeconds = $since->diffInSeconds(now());
        $downSeconds = 0;

        for ($i = 0; $i < $results->count() - 1; $i++) {
            $current = $results[$i];
            $next = $results[$i + 1];

            if ($current->status === 'down') {
                $downSeconds += $current->checked_at->diffInSeconds($next->checked_at);
            }
        }

        $lastResult = $results->last();
        if ($lastResult->status === 'down') {
            $downSeconds += $lastResult->checked_at->diffInSeconds(now());
        }

        if ($totalSeconds <= 0) {
            return 100.0;
        }

        $uptime = max(0, min(100, (($totalSeconds - $downSeconds) / $totalSeconds) * 100));

        return round($uptime, 2);
    }

    public function getResponseTimeStats(Monitor $monitor, int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $stats = $monitor->checkResults()
            ->where('checked_at', '>=', $since)
            ->whereNotNull('response_time_ms')
            ->selectRaw('
                AVG(response_time_ms) as avg_response_time,
                MIN(response_time_ms) as min_response_time,
                MAX(response_time_ms) as max_response_time
            ')
            ->first();

        return [
            'avg' => $stats->avg_response_time ? round($stats->avg_response_time) : 0,
            'min' => $stats->min_response_time ?? 0,
            'max' => $stats->max_response_time ?? 0,
        ];
    }

    public function getTeamUptimeStats(int $teamId, int $hours = 24): array
    {
        $monitors = Monitor::where('team_id', $teamId)->get();

        $total = $monitors->count();
        $up = $monitors->where('status', 'up')->count();
        $down = $monitors->where('status', 'down')->count();
        $paused = $monitors->where('is_paused', true)->count();

        $activeUptimes = [];
        foreach ($monitors->where('is_paused', false) as $monitor) {
            $activeUptimes[] = $this->calculateTimeWeightedUptime($monitor, $hours);
        }

        $avgUptime = count($activeUptimes) > 0
            ? round(array_sum($activeUptimes) / count($activeUptimes), 2)
            : 100.0;

        return [
            'total' => $total,
            'up' => $up,
            'down' => $down,
            'paused' => $paused,
            'uptime_24h' => $avgUptime,
        ];
    }
}
