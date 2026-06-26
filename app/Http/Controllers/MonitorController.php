<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\MonitorGroup;
use App\Models\CheckResult;
use App\Services\CheckService;
use App\Services\UptimeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MonitorController extends Controller
{
    public function __construct(
        private UptimeCalculator $uptimeCalculator,
        private CheckService $checkService
    ) {}

    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        $query = $team->monitors()->with('group')
            ->select('monitors.*')
            ->selectSub(
                fn ($q) => $q->from('monitor_ssl')
                    ->whereColumn('monitor_ssl.monitor_id', 'monitors.id')
                    ->orderBy('checked_at', 'desc')
                    ->limit(1)
                    ->select('days_left'),
                'ssl_days_left'
            )
            ->selectSub(
                fn ($q) => $q->from('monitor_domain')
                    ->whereColumn('monitor_domain.monitor_id', 'monitors.id')
                    ->orderBy('checked_at', 'desc')
                    ->limit(1)
                    ->select('days_left'),
                'domain_days_left'
            );

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('url', 'like', "%{$request->search}%");
            });
        }

        $monitors = $query->orderBy('name')->paginate(25)->withQueryString();
        $groups = $team->groups()->orderBy('name')->get();

        return view('monitors.index', compact('monitors', 'groups'));
    }

    public function create(Request $request)
    {
        $team = $request->user()->currentTeam;
        $groups = $team->groups()->orderBy('name')->get();

        return view('monitors.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $team = $request->user()->currentTeam;

        if (!$team->canAddMonitor()) {
            return back()->withErrors(['url' => 'Monitor limit reached for your plan. Please upgrade.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'url' => 'required|url|max:255',
            'type' => 'required|in:http,https,tcp,dns,keyword',
            'method' => 'required|in:GET,POST,PUT,HEAD',
            'group_id' => 'nullable|exists:monitor_groups,id',
            'check_interval_seconds' => 'required|integer|min:15|max:3600',
            'expected_status_code' => 'nullable|integer|min:100|max:599',
            'expected_keyword' => 'nullable|string|max:255',
            'tcp_host' => 'nullable|string|max:255',
            'tcp_port' => 'nullable|integer|min:1|max:65535',
            'alert_on_down' => 'boolean',
            'alert_on_up' => 'boolean',
            'alert_on_ssl_expiry' => 'boolean',
            'alert_on_domain_expiry' => 'boolean',
            'ssl_alert_threshold_days' => 'nullable|integer|min:1|max:365',
            'domain_alert_threshold_days' => 'nullable|integer|min:1|max:365',
            'ssl_enabled' => 'boolean',
            'domain_enabled' => 'boolean',
        ]);

        $validated['team_id'] = $team->id;
        $validated['slug'] = Str::random(12);
        $validated['next_check_at'] = now();
        $validated['status'] = 'unknown';
        $validated['is_paused'] = false;

        $monitor = Monitor::create($validated);

        try {
            $this->checkService->runHttpCheck($monitor);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Initial check failed", ['monitor_id' => $monitor->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('monitors.show', $monitor)
            ->with('success', 'Monitor created and first check completed.');
    }

    public function show(Request $request, Monitor $monitor)
    {
        $this->authorizeMonitor($request, $monitor);

        $checkResults = $monitor->checkResults()
            ->orderBy('checked_at', 'desc')
            ->limit(50)
            ->get();

        $uptime24 = $this->uptimeCalculator->calculateTimeWeightedUptime($monitor, 24);
        $uptime7d = $this->uptimeCalculator->calculateTimeWeightedUptime($monitor, 168);
        $uptime30d = $this->uptimeCalculator->calculateTimeWeightedUptime($monitor, 720);

        $responseStats = $this->uptimeCalculator->getResponseTimeStats($monitor, 24);

        $latestSsl = $monitor->sslChecks()->latest('checked_at')->first();
        $latestDomain = $monitor->domainChecks()->latest('checked_at')->first();

        return view('monitors.show', compact(
            'monitor', 'checkResults', 'uptime24', 'uptime7d', 'uptime30d',
            'responseStats', 'latestSsl', 'latestDomain'
        ));
    }

    public function edit(Request $request, Monitor $monitor)
    {
        $this->authorizeMonitor($request, $monitor);

        $team = $request->user()->currentTeam;
        $groups = $team->groups()->orderBy('name')->get();

        return view('monitors.edit', compact('monitor', 'groups'));
    }

    public function update(Request $request, Monitor $monitor)
    {
        $this->authorizeMonitor($request, $monitor);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'url' => 'required|url|max:255',
            'type' => 'required|in:http,https,tcp,dns,keyword',
            'method' => 'required|in:GET,POST,PUT,HEAD',
            'group_id' => 'nullable|exists:monitor_groups,id',
            'check_interval_seconds' => 'required|integer|min:15|max:3600',
            'expected_status_code' => 'nullable|integer|min:100|max:599',
            'expected_keyword' => 'nullable|string|max:255',
            'tcp_host' => 'nullable|string|max:255',
            'tcp_port' => 'nullable|integer|min:1|max:65535',
            'alert_on_down' => 'boolean',
            'alert_on_up' => 'boolean',
            'alert_on_ssl_expiry' => 'boolean',
            'alert_on_domain_expiry' => 'boolean',
            'ssl_alert_threshold_days' => 'nullable|integer|min:1|max:365',
            'domain_alert_threshold_days' => 'nullable|integer|min:1|max:365',
            'ssl_enabled' => 'boolean',
            'domain_enabled' => 'boolean',
        ]);

        $validated['next_check_at'] = now()->addSeconds((int) $validated['check_interval_seconds']);

        $monitor->update($validated);

        return redirect()->route('monitors.show', $monitor)
            ->with('success', 'Monitor updated successfully.');
    }

    public function destroy(Request $request, Monitor $monitor)
    {
        $this->authorizeMonitor($request, $monitor);

        $monitor->delete();

        return redirect()->route('monitors.index')
            ->with('success', 'Monitor deleted successfully.');
    }

    public function togglePause(Request $request, Monitor $monitor)
    {
        $this->authorizeMonitor($request, $monitor);

        if ($monitor->is_paused) {
            $monitor->resume();
            $message = 'Monitor resumed.';
        } else {
            $monitor->pause();
            $message = 'Monitor paused.';
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'status' => $monitor->fresh()->status]);
        }

        return back()->with('success', $message);
    }

    public function checkNow(Request $request, Monitor $monitor)
    {
        $this->authorizeMonitor($request, $monitor);

        $messages = [];

        try {
            $this->checkService->runHttpCheck($monitor);
            $messages[] = 'HTTP check completed.';
        } catch (\Exception $e) {
            $messages[] = 'HTTP check failed: ' . $e->getMessage();
        }

        try {
            $result = $this->checkService->runSslCheck($monitor);
            if ($result['status'] !== 'skipped') {
                $messages[] = 'SSL check completed.';
            }
        } catch (\Exception $e) {
            $messages[] = 'SSL check failed: ' . $e->getMessage();
        }

        try {
            $result = $this->checkService->runDomainCheck($monitor);
            if ($result['status'] !== 'skipped') {
                $messages[] = 'Domain check completed.';
            }
        } catch (\Exception $e) {
            $messages[] = 'Domain check failed: ' . $e->getMessage();
        }

        $message = implode(' ', $messages);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function logs(Request $request, Monitor $monitor)
    {
        $this->authorizeMonitor($request, $monitor);

        $results = $monitor->checkResults()
            ->orderBy('checked_at', 'desc')
            ->paginate(50)
            ->withQueryString();

        return view('monitors.logs', compact('monitor', 'results'));
    }

    private function authorizeMonitor(Request $request, Monitor $monitor): void
    {
        abort_if($monitor->team_id !== $request->user()->current_team_id, 403);
    }
}
