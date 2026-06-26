<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\StatusPage;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        $incidents = $team->incidents()
            ->with('statusPage')
            ->latest()
            ->paginate(25);

        return view('incidents.index', compact('incidents'));
    }

    public function create(Request $request)
    {
        $team = $request->user()->currentTeam;
        $statusPages = $team->statusPages()->orderBy('title')->get();
        $monitors = $team->monitors()->orderBy('name')->get();

        return view('incidents.create', compact('statusPages', 'monitors'));
    }

    public function store(Request $request)
    {
        $team = $request->user()->currentTeam;

        $validated = $request->validate([
            'status_page_id' => 'required|exists:status_pages,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'impact' => 'required|in:minor,major,critical',
            'monitor_ids' => 'nullable|array',
            'monitor_ids.*' => 'exists:monitors,id',
        ]);

        abort_if(
            $team->statusPages()->where('id', $validated['status_page_id'])->doesntExist(),
            403
        );

        $incident = $team->incidents()->create([
            'status_page_id' => $validated['status_page_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'impact' => $validated['impact'],
            'status' => 'open',
            'started_at' => now(),
        ]);

        if (!empty($validated['monitor_ids'])) {
            $monitorIds = collect($validated['monitor_ids'])
                ->filter(fn ($id) => $team->monitors()->where('id', $id)->exists())
                ->toArray();
            $incident->monitors()->sync($monitorIds);
        }

        $incident->updates()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['description'],
            'status' => 'open',
        ]);

        return redirect()->route('incidents.show', $incident)
            ->with('success', 'Incident created successfully.');
    }

    public function show(Request $request, Incident $incident)
    {
        abort_if($incident->team_id !== $request->user()->current_team_id, 403);

        $incident->load(['statusPage', 'monitors', 'updates' => function ($q) {
            $q->with('user')->latest();
        }]);

        return view('incidents.show', compact('incident'));
    }

    public function updateStatus(Request $request, Incident $incident)
    {
        abort_if($incident->team_id !== $request->user()->current_team_id, 403);

        $validated = $request->validate([
            'status' => 'required|in:open,investigating,identified,monitoring,resolved',
            'message' => 'required|string',
        ]);

        $incident->update([
            'status' => $validated['status'],
            'resolved_at' => $validated['status'] === 'resolved' ? now() : null,
        ]);

        $incident->updates()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Incident status updated.');
    }

    public function destroy(Request $request, Incident $incident)
    {
        abort_if($incident->team_id !== $request->user()->current_team_id, 403);

        $incident->delete();

        return redirect()->route('incidents.index')
            ->with('success', 'Incident deleted successfully.');
    }
}
