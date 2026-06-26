<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Create Incident</h2>
            <a href="{{ route('incidents.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Back to Incidents</a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('incidents.store') }}" class="bg-white shadow rounded-lg border border-gray-200">
            @csrf
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Incident Details</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div>
                    <x-input-label for="status_page_id" value="Status Page" />
                    <select id="status_page_id" name="status_page_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        <option value="">Select a status page</option>
                        @foreach($statusPages as $page)
                        <option value="{{ $page->id }}" {{ old('status_page_id') == $page->id ? 'selected' : '' }}>{{ $page->title }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status_page_id')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="title" value="Title" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required autofocus />
                    <x-input-error :messages="$errors->get('title')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="impact" value="Impact" />
                    <select id="impact" name="impact" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        <option value="none" {{ old('impact') === 'none' ? 'selected' : '' }}>None</option>
                        <option value="minor" {{ old('impact') === 'minor' ? 'selected' : '' }}>Minor</option>
                        <option value="major" {{ old('impact') === 'major' ? 'selected' : '' }}>Major</option>
                        <option value="critical" {{ old('impact') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                    <x-input-error :messages="$errors->get('impact')" class="mt-1" />
                </div>

                <div>
                    <x-input-label value="Affected Monitors" />
                    <div class="mt-2 space-y-2 max-h-60 overflow-y-auto border border-gray-200 rounded-md p-3">
                        @forelse($monitors as $monitor)
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="monitor_ids[]" value="{{ $monitor->id }}" {{ in_array($monitor->id, old('monitor_ids', [])) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">{{ $monitor->name }}</span>
                        </label>
                        @empty
                        <p class="text-sm text-gray-500">No monitors available.</p>
                        @endforelse
                    </div>
                    <x-input-error :messages="$errors->get('monitor_ids')" class="mt-1" />
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('incidents.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Cancel
                </a>
                <x-primary-button>Create Incident</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
