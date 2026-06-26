<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">{{ $team->name }}</h2>
            <a href="{{ route('teams.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Back to Teams</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white shadow rounded-lg border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Members</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                @foreach($team->members as $member)
                <li class="px-6 py-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $member->name }}</p>
                        <p class="text-xs text-gray-500">{{ $member->email }}</p>
                    </div>
                    @if($member->id === $team->user_id)
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">Owner</span>
                    @elseif(isset($member->pivot->role))
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 capitalize">{{ $member->pivot->role }}</span>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>

        <div class="bg-white shadow rounded-lg border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Invite Member</h3>
            </div>
            <form method="POST" action="{{ route('teams.invite', $team) }}" class="px-6 py-5">
                @csrf
                <div class="flex gap-3">
                    <div class="flex-1">
                        <x-input-label for="email" value="Email Address" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" required placeholder="member@example.com" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                    <div class="flex items-end">
                        <x-primary-button>Send Invite</x-primary-button>
                    </div>
                </div>
            </form>
        </div>

        @if($team->id !== $currentTeam->id)
        <div class="bg-white shadow rounded-lg border border-red-200">
            <div class="px-6 py-5">
                <h3 class="text-lg font-medium text-red-800">Danger Zone</h3>
                <p class="mt-1 text-sm text-gray-600">Deleting this team will remove all associated data. This action cannot be undone.</p>
                <form method="POST" action="{{ route('teams.destroy', $team) }}" class="mt-4" onsubmit="return confirm('Are you sure you want to delete this team? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>Delete Team</x-danger-button>
                </form>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
