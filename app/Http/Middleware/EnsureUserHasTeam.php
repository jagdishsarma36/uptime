<?php

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->current_team_id && $user->currentTeam) {
            return $next($request);
        }

        $firstTeam = $user->teams()->first();

        if ($firstTeam) {
            $user->update(['current_team_id' => $firstTeam->id]);
            return $next($request);
        }

        return redirect()->route('teams.create');
    }
}
