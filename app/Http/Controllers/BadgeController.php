<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function uptime(Request $request, string $slug)
    {
        $monitor = Monitor::where('slug', $request->monitor_slug ?? '')
            ->firstOrFail();

        $period = $request->get('period', '24h');
        $hours = match ($period) {
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24,
        };

        $calculator = new \App\Services\UptimeCalculator();
        $uptime = $calculator->calculateTimeWeightedUptime($monitor, $hours);

        $color = $uptime >= 99 ? '#22c55e' : ($uptime >= 95 ? '#eab308' : '#ef4444');

        return response()
            ->header('Content-Type', 'text/html')
            ->header('Cache-Control', 'public, max-age=300')
            ->view('badges.uptime', compact('monitor', 'uptime', 'period', 'color'));
    }

    public function status(Request $request)
    {
        $monitor = Monitor::where('slug', $request->monitor_slug ?? '')
            ->firstOrFail();

        $status = $monitor->status;
        $color = match ($status) {
            'up' => '#22c55e',
            'down' => '#ef4444',
            'paused' => '#6b7280',
            default => '#d1d5db',
        };

        $label = match ($status) {
            'up' => 'Operational',
            'down' => 'Down',
            'paused' => 'Paused',
            default => 'Unknown',
        };

        return response()
            ->header('Content-Type', 'text/html')
            ->header('Cache-Control', 'public, max-age=60')
            ->view('badges.status', compact('monitor', 'status', 'color', 'label'));
    }
}
