<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Edit Status Page</h2>
            <a href="{{ route('status-pages.show', $statusPage) }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Back to Status Page</a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('status-pages.update', $statusPage) }}" class="bg-white shadow rounded-lg border border-gray-200">
            @csrf
            @method('PUT')
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Status Page Details</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div>
                    <x-input-label for="title" value="Title" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $statusPage->title)" required autofocus />
                    <x-input-error :messages="$errors->get('title')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $statusPage->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="theme" value="Theme" />
                    <select id="theme" name="theme" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="light" {{ old('theme', $statusPage->theme) === 'light' ? 'selected' : '' }}>Light</option>
                        <option value="dark" {{ old('theme', $statusPage->theme) === 'dark' ? 'selected' : '' }}>Dark</option>
                    </select>
                    <x-input-error :messages="$errors->get('theme')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="password" value="Password Protection (optional)" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" placeholder="Leave blank to keep current" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="custom_domain" value="Custom Domain (optional)" />
                    <x-text-input id="custom_domain" name="custom_domain" type="text" class="mt-1 block w-full" :value="old('custom_domain', $statusPage->custom_domain)" placeholder="status.example.com" />
                    <x-input-error :messages="$errors->get('custom_domain')" class="mt-1" />
                </div>

                <label class="flex items-center gap-3">
                    <input type="checkbox" name="published" value="1" {{ old('published', $statusPage->published) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">Published</span>
                </label>
            </div>

            <div class="px-6 py-5 border-t border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Monitors</h3>
                <div class="space-y-2 max-h-60 overflow-y-auto">
                    @forelse($monitors as $monitor)
                    <label class="flex items-center gap-3 p-2 rounded hover:bg-gray-100">
                        <input type="checkbox" name="monitor_ids[]" value="{{ $monitor->id }}" {{ in_array($monitor->id, old('monitor_ids', $selectedMonitorIds)) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">{{ $monitor->name }}</span>
                        <span class="text-xs text-gray-400">{{ $monitor->url }}</span>
                    </label>
                    @empty
                    <p class="text-sm text-gray-500">No monitors available.</p>
                    @endforelse
                </div>
                <x-input-error :messages="$errors->get('monitor_ids')" class="mt-1" />
            </div>

            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('status-pages.show', $statusPage) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Cancel
                </a>
                <x-primary-button>Save Changes</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
