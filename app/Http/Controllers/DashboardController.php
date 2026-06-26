<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\MonitorDomain;
use App\Models\MonitorSsl;
use App\Services\CheckService;
use App\Services\UptimeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        private UptimeCalculator $uptimeCalculator,
        private CheckService $checkService
    ) {}

    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        $dueMonitors = Monitor::where('team_id', $team->id)
            ->where('is_paused', false)
            ->where('next_check_at', '<=', now())
            ->limit(20)
            ->get();

        foreach ($dueMonitors as $monitor) {
            try {
                $this->checkService->runHttpCheck($monitor);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Dashboard auto-check failed", [
                    'monitor_id' => $monitor->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $stats = $this->uptimeCalculator->getTeamUptimeStats($team->id, 24);

        $monitors = $team->monitors()
            ->with('group')
            ->orderByRaw("CASE status WHEN 'down' THEN 1 WHEN 'up' THEN 2 WHEN 'unknown' THEN 3 WHEN 'paused' THEN 4 ELSE 5 END")
            ->orderBy('name')
            ->get();

        $sslWarnings = MonitorSsl::whereHas('monitor', fn ($q) => $q->where('team_id', $team->id))
            ->where('days_left', '>', 0)
            ->where('days_left', '<=', 20)
            ->where('is_valid', true)
            ->with('monitor')
            ->orderBy('days_left')
            ->limit(10)
            ->get();

        $domainWarnings = MonitorDomain::whereHas('monitor', fn ($q) => $q->where('team_id', $team->id))
            ->where('days_left', '>', 0)
            ->where('days_left', '<=', 20)
            ->with('monitor')
            ->orderBy('days_left')
            ->limit(10)
            ->get();

        $recentIncidents = $team->incidents()
            ->where('status', '!=', 'resolved')
            ->with('statusPage')
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'monitors', 'sslWarnings', 'domainWarnings', 'recentIncidents'));
    }

    public function stats(Request $request)
    {
        $team = $request->user()->currentTeam;
        $stats = $this->uptimeCalculator->getTeamUptimeStats($team->id, 24);
        return response()->json($stats);
    }
}
