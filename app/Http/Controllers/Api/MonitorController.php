<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Monitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $team = $request->user()->currentTeam;

        $monitors = $team->monitors()
            ->with('group')
            ->orderBy('name')
            ->paginate(50);

        return response()->json($monitors);
    }

    public function store(Request $request): JsonResponse
    {
        $team = $request->user()->currentTeam;

        if (!$team->canAddMonitor()) {
            return response()->json(['error' => 'Monitor limit reached'], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'url' => 'required|url|max:255',
            'type' => 'required|in:http,https,tcp,dns,keyword',
            'method' => 'required|in:GET,POST,PUT,HEAD',
            'check_interval_seconds' => 'required|integer|min:15|max:3600',
        ]);

        $validated['team_id'] = $team->id;
        $validated['slug'] = \Illuminate\Support\Str::random(12);
        $validated['next_check_at'] = now();
        $validated['status'] = 'unknown';
        $validated['is_paused'] = false;

        $monitor = Monitor::create($validated);

        return response()->json($monitor, 201);
    }

    public function show(Request $request, Monitor $monitor): JsonResponse
    {
        if ($monitor->team_id !== $request->user()->current_team_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $monitor->load('group', 'latestCheck');

        return response()->json($monitor);
    }

    public function update(Request $request, Monitor $monitor): JsonResponse
    {
        if ($monitor->team_id !== $request->user()->current_team_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'url' => 'required|url|max:255',
            'type' => 'required|in:http,https,tcp,dns,keyword',
            'method' => 'required|in:GET,POST,PUT,HEAD',
            'check_interval_seconds' => 'required|integer|min:15|max:3600',
        ]);

        $monitor->update($validated);

        return response()->json($monitor);
    }

    public function destroy(Request $request, Monitor $monitor): JsonResponse
    {
        if ($monitor->team_id !== $request->user()->current_team_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $monitor->delete();

        return response()->json(['message' => 'Monitor deleted']);
    }

    public function togglePause(Request $request, Monitor $monitor): JsonResponse
    {
        if ($monitor->team_id !== $request->user()->current_team_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($monitor->is_paused) {
            $monitor->resume();
        } else {
            $monitor->pause();
        }

        return response()->json($monitor->fresh());
    }

    public function checkNow(Request $request, Monitor $monitor): JsonResponse
    {
        if ($monitor->team_id !== $request->user()->current_team_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        \App\Jobs\HttpCheckJob::dispatch($monitor)->onQueue('checks');

        return response()->json(['message' => 'Check dispatched']);
    }

    public function logs(Request $request, Monitor $monitor): JsonResponse
    {
        if ($monitor->team_id !== $request->user()->current_team_id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $logs = $monitor->checkResults()
            ->orderBy('checked_at', 'desc')
            ->paginate(50);

        return response()->json($logs);
    }
}
