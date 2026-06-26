<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Create Team</h2>
            <a href="{{ route('teams.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Back to Teams</a>
        </div>
    </x-slot>

    <div class="max-w-lg">
        <form method="POST" action="{{ route('teams.store') }}" class="bg-white shadow rounded-lg border border-gray-200">
            @csrf
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Team Details</h3>
            </div>
            <div class="px-6 py-5">
                <div>
                    <x-input-label for="name" value="Team Name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('teams.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Cancel
                </a>
                <x-primary-button>Create Team</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
