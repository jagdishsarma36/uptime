<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\User;

class TeamService
{
    public function createTeam(User $owner, string $name, string $plan = 'free'): Team
    {
        $team = Team::create([
            'user_id' => $owner->id,
            'name' => $name,
            'plan' => $plan,
            'max_monitors' => $this->getPlanLimits($plan)['max_monitors'],
            'check_interval_seconds' => $this->getPlanLimits($plan)['check_interval_seconds'],
            'retention_days' => $this->getPlanLimits($plan)['retention_days'],
            'trial_ends_at' => $plan !== 'free' ? now()->addDays(14) : null,
        ]);

        $team->members()->attach($owner->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $owner->update(['current_team_id' => $team->id]);

        AuditLog::log('team.created', $team, null, $team->toArray());

        return $team;
    }

    public function inviteMember(Team $team, string $email, User $inviter, string $role = 'member'): void
    {
        $existingUser = User::where('email', $email)->first();

        if ($existingUser && $team->members()->where('user_id', $existingUser->id)->exists()) {
            throw new \Exception('User is already a member of this team.');
        }

        $team->invitations()->create([
            'user_id' => $inviter->id,
            'email' => $email,
            'token' => \Illuminate\Support\Str::random(64),
            'role' => $role,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function acceptInvitation(string $token, User $user): Team
    {
        $invitation = \App\Models\TeamInvitation::where('token', $token)
            ->where('expires_at', '>', now())
            ->whereNull('accepted_at')
            ->firstOrFail();

        if ($invitation->email !== $user->email) {
            throw new \Exception('This invitation was sent to a different email address.');
        }

        $team = $invitation->team;

        $team->members()->attach($user->id, [
            'role' => $invitation->role,
            'joined_at' => now(),
        ]);

        $invitation->update(['accepted_at' => now()]);

        if (!$user->current_team_id) {
            $user->update(['current_team_id' => $team->id]);
        }

        AuditLog::log('team.member_added', $team, null, [
            'user_id' => $user->id,
            'role' => $invitation->role,
        ]);

        return $team;
    }

    public function removeMember(Team $team, User $member): void
    {
        if ($member->id === $team->user_id) {
            throw new \Exception('Cannot remove the team owner.');
        }

        $team->members()->detach($member->id);

        if ($member->current_team_id === $team->id) {
            $nextTeam = $member->teams()->first();
            $member->update(['current_team_id' => $nextTeam?->id]);
        }

        AuditLog::log('team.member_removed', $team, null, ['user_id' => $member->id]);
    }

    public function switchTeam(User $user, Team $team): void
    {
        if (!$user->teams()->where('teams.id', $team->id)->exists()) {
            throw new \Exception('You are not a member of this team.');
        }

        $user->update(['current_team_id' => $team->id]);
    }

    public function updatePlan(Team $team, string $plan): void
    {
        $oldPlan = $team->plan;
        $limits = $this->getPlanLimits($plan);

        $team->update([
            'plan' => $plan,
            'max_monitors' => $limits['max_monitors'],
            'check_interval_seconds' => $limits['check_interval_seconds'],
            'retention_days' => $limits['retention_days'],
        ]);

        AuditLog::log('team.plan_updated', $team, ['plan' => $oldPlan], ['plan' => $plan]);
    }

    public function getPlanLimits(string $plan): array
    {
        return match ($plan) {
            'free' => ['max_monitors' => 5, 'check_interval_seconds' => 300, 'retention_days' => 30],
            'pro' => ['max_monitors' => 100, 'check_interval_seconds' => 60, 'retention_days' => 90],
            'business' => ['max_monitors' => 1000, 'check_interval_seconds' => 30, 'retention_days' => 365],
            'enterprise' => ['max_monitors' => 5000, 'check_interval_seconds' => 15, 'retention_days' => 730],
            default => ['max_monitors' => 5, 'check_interval_seconds' => 300, 'retention_days' => 30],
        };
    }
}
