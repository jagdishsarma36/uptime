<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\StatusPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StatusPageController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        $statusPages = $team->statusPages()
            ->withCount('monitors')
            ->withCount(['incidents' => fn ($q) => $q->where('status', '!=', 'resolved')])
            ->latest()
            ->paginate(20);

        return view('status-pages.index', compact('statusPages'));
    }

    public function create(Request $request)
    {
        $team = $request->user()->currentTeam;
        $monitors = $team->monitors()->orderBy('name')->get();

        return view('status-pages.create', compact('monitors'));
    }

    public function store(Request $request)
    {
        $team = $request->user()->currentTeam;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'theme' => 'required|in:light,dark',
            'monitor_ids' => 'required|array|min:1',
            'monitor_ids.*' => 'exists:monitors,id',
            'password' => 'nullable|string|min:6|max:255',
            'custom_domain' => 'nullable|url|max:255',
        ]);

        $monitorIds = collect($validated['monitor_ids'])
            ->filter(fn ($id) => $team->monitors()->where('id', $id)->exists())
            ->toArray();

        $statusPage = $team->statusPages()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'theme' => $validated['theme'],
            'slug' => Str::slug($validated['title']) . '-' . Str::random(6),
            'public_key' => Str::random(64),
            'password' => isset($validated['password']) ? bcrypt($validated['password']) : null,
            'custom_domain' => $validated['custom_domain'] ?? null,
        ]);

        $statusPage->monitors()->attach($monitorIds);

        return redirect()->route('status-pages.show', $statusPage)
            ->with('success', 'Status page created successfully.');
    }

    public function show(Request $request, StatusPage $statusPage)
    {
        abort_if($statusPage->team_id !== $request->user()->current_team_id, 403);

        $statusPage->load(['monitors', 'incidents' => function ($q) {
            $q->latest()->limit(10);
        }]);

        return view('status-pages.show', compact('statusPage'));
    }

    public function edit(Request $request, StatusPage $statusPage)
    {
        abort_if($statusPage->team_id !== $request->user()->current_team_id, 403);

        $team = $request->user()->currentTeam;
        $monitors = $team->monitors()->orderBy('name')->get();
        $selectedMonitorIds = $statusPage->monitors()->pluck('monitors.id')->toArray();

        return view('status-pages.edit', compact('statusPage', 'monitors', 'selectedMonitorIds'));
    }

    public function update(Request $request, StatusPage $statusPage)
    {
        abort_if($statusPage->team_id !== $request->user()->current_team_id, 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'theme' => 'required|in:light,dark',
            'monitor_ids' => 'required|array|min:1',
            'monitor_ids.*' => 'exists:monitors,id',
            'password' => 'nullable|string|min:6|max:255',
            'custom_domain' => 'nullable|url|max:255',
            'is_published' => 'boolean',
        ]);

        $team = $request->user()->currentTeam;
        $monitorIds = collect($validated['monitor_ids'])
            ->filter(fn ($id) => $team->monitors()->where('id', $id)->exists())
            ->toArray();

        $statusPage->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'theme' => $validated['theme'],
            'password' => isset($validated['password']) && $validated['password']
                ? bcrypt($validated['password'])
                : $statusPage->password,
            'custom_domain' => $validated['custom_domain'] ?? null,
            'is_published' => $validated['is_published'] ?? true,
        ]);

        $statusPage->monitors()->sync($monitorIds);

        return redirect()->route('status-pages.show', $statusPage)
            ->with('success', 'Status page updated successfully.');
    }

    public function destroy(Request $request, StatusPage $statusPage)
    {
        abort_if($statusPage->team_id !== $request->user()->current_team_id, 403);

        $statusPage->delete();

        return redirect()->route('status-pages.index')
            ->with('success', 'Status page deleted successfully.');
    }
}
