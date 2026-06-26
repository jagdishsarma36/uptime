<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\MonitorGroup;
use App\Models\MonitorDomain;
use App\Models\MonitorSsl;
use App\Models\StatusPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $teams = $user->teams()->withCount('members')->with('owner')->get();

        return view('teams.index', compact('teams'));
    }

    public function create()
    {
        return view('teams.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
        ]);

        $teamService = new \App\Services\TeamService();
        $team = $teamService->createTeam($request->user(), $validated['name']);

        return redirect()->route('dashboard')
            ->with('success', "Team '{$team->name}' created successfully.");
    }

    public function show(Request $request, int $teamId)
    {
        $user = $request->user();
        $team = $user->teams()->findOrFail($teamId);

        $team->load(['members', 'monitors', 'statusPages']);

        return view('teams.show', compact('team'));
    }

    public function switch(Request $request, int $teamId)
    {
        $teamService = new \App\Services\TeamService();
        $teamService->switchTeam($request->user(), \App\Models\Team::findOrFail($teamId));

        return redirect()->route('dashboard')
            ->with('success', 'Switched team successfully.');
    }

    public function invite(Request $request, int $teamId)
    {
        $user = $request->user();
        $team = $user->teams()->findOrFail($teamId);

        abort_unless($team->pivot->role === 'owner' || $team->pivot->role === 'admin', 403);

        $validated = $request->validate([
            'email' => 'required|email|max:191',
            'role' => 'required|in:admin,member,viewer',
        ]);

        $teamService = new \App\Services\TeamService();
        $teamService->inviteMember($team, $validated['email'], $user, $validated['role']);

        return back()->with('success', 'Invitation sent successfully.');
    }

    public function removeMember(Request $request, int $teamId, int $userId)
    {
        $user = $request->user();
        $team = $user->teams()->findOrFail($teamId);

        abort_unless($team->pivot->role === 'owner', 403);

        $member = \App\Models\User::findOrFail($userId);
        $teamService = new \App\Services\TeamService();
        $teamService->removeMember($team, $member);

        return back()->with('success', 'Member removed successfully.');
    }

    public function destroy(Request $request, int $teamId)
    {
        $user = $request->user();
        $team = $user->teams()->findOrFail($teamId);

        abort_unless($team->pivot->role === 'owner', 403);

        $team->delete();

        $newTeam = $user->teams()->first();
        if ($newTeam) {
            $user->update(['current_team_id' => $newTeam->id]);
        }

        return redirect()->route('teams.index')
            ->with('success', 'Team deleted successfully.');
    }
}
