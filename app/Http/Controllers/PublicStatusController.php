<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Services\UptimeCalculator;
use Illuminate\Http\Request;

class PublicStatusController extends Controller
{
    public function __construct(
        private UptimeCalculator $uptimeCalculator
    ) {}

    public function show(Request $request, string $slug)
    {
        $statusPage = StatusPage::with('team')->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        if ($statusPage->password && !$this->checkPassword($request, $statusPage)) {
            return view('status-pages.password', ['statusPage' => $statusPage]);
        }

        $monitors = $statusPage->monitors()
            ->orderBy('name')
            ->get()
            ->map(function ($monitor) {
                $monitor->uptime_24h = $this->uptimeCalculator->calculateTimeWeightedUptime($monitor, 24);
                $monitor->uptime_7d = $this->uptimeCalculator->calculateTimeWeightedUptime($monitor, 168);
                return $monitor;
            });

        $activeIncidents = $statusPage->activeIncidents()
            ->with('updates')
            ->latest()
            ->get();

        $teamName = $statusPage->team->name ?? '';

        $downCount = $monitors->where('status', 'down')->count();
        $totalCount = $monitors->count();
        $overallStatus = 'up';
        if ($downCount === $totalCount && $totalCount > 0) {
            $overallStatus = 'down';
        } elseif ($downCount > 0) {
            $overallStatus = 'degraded';
        }

        return view('status-pages.public', compact('statusPage', 'monitors', 'activeIncidents', 'teamName', 'overallStatus'));
    }

    public function monitor(Request $request, string $slug, int $monitorId)
    {
        $statusPage = StatusPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        if ($statusPage->password && !$this->checkPassword($request, $statusPage)) {
            return redirect()->route('status.public', $slug);
        }

        $monitor = $statusPage->monitors()
            ->where('monitors.id', $monitorId)
            ->firstOrFail();

        $uptime24 = $this->uptimeCalculator->calculateTimeWeightedUptime($monitor, 24);
        $uptime7d = $this->uptimeCalculator->calculateTimeWeightedUptime($monitor, 168);
        $uptime30d = $this->uptimeCalculator->calculateTimeWeightedUptime($monitor, 720);

        $checkResults = $monitor->checkResults()
            ->orderBy('checked_at', 'desc')
            ->limit(100)
            ->get();

        $responseStats = $this->uptimeCalculator->getResponseTimeStats($monitor, 24);

        return view('status-pages.monitor', compact(
            'statusPage', 'monitor', 'uptime24', 'uptime7d', 'uptime30d',
            'checkResults', 'responseStats'
        ));
    }

    public function checkPassword(Request $request, StatusPage $statusPage): bool
    {
        $sessionKey = "status_page_{$statusPage->id}_auth";

        if ($request->session()->get($sessionKey)) {
            return true;
        }

        if ($request->isMethod('post') && $request->input('password')) {
            if (hash('sha256', $request->input('password')) === $statusPage->password) {
                $request->session()->put($sessionKey, true);
                return true;
            }
        }

        return false;
    }

    public function verifyPassword(Request $request, string $slug)
    {
        $statusPage = StatusPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        if ($this->checkPassword($request, $statusPage)) {
            return redirect()->route('status.public', $slug);
        }

        return back()->withErrors(['password' => 'Invalid password.']);
    }
}
