<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::create(['name' => 'owner', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'member', 'guard_name' => 'web']);
        Role::create(['name' => 'viewer', 'guard_name' => 'web']);

        $teamService = new TeamService();

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@uptimeguard.io',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'timezone' => 'UTC',
        ]);

        $user->assignRole('super-admin');

        $team = $teamService->createTeam($user, 'My Team', 'free');

        $group = $team->groups()->create(['name' => 'Production']);

        $monitors = [
            ['name' => 'Google', 'url' => 'https://www.google.com', 'type' => 'https', 'group_id' => $group->id],
            ['name' => 'GitHub', 'url' => 'https://github.com', 'type' => 'https', 'group_id' => $group->id],
            ['name' => 'Stack Overflow', 'url' => 'https://stackoverflow.com', 'type' => 'https', 'group_id' => $group->id],
        ];

        foreach ($monitors as $data) {
            $team->monitors()->create(array_merge($data, [
                'slug' => \Illuminate\Support\Str::random(12),
                'next_check_at' => now(),
                'status' => 'unknown',
            ]));
        }
    }
}
