<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">{{ $statusPage->title }}</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('status-pages.edit', $statusPage) }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Edit
                </a>
                <form method="POST" action="{{ route('status-pages.destroy', $statusPage) }}" onsubmit="return confirm('Are you sure you want to delete this status page?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Title</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $statusPage->title }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Slug</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $statusPage->slug }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Public Key</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $statusPage->public_key }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Public URL</dt>
                    <dd class="mt-1 text-sm">
                        <a href="{{ route('status.public', $statusPage->slug) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900">{{ route('status.public', $statusPage->slug) }}</a>
                    </dd>
                </div>
            </div>
            @if($statusPage->description)
            <div class="mt-4 pt-4 border-t border-gray-200">
                <dt class="text-sm font-medium text-gray-500">Description</dt>
                <dd class="mt-1 text-sm text-gray-700">{{ $statusPage->description }}</dd>
            </div>
            @endif
        </div>

        <div class="bg-white shadow rounded-lg border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Monitors</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                @forelse($statusPage->monitors as $monitor)
                <li class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="h-2.5 w-2.5 rounded-full {{ $monitor->status === 'up' ? 'bg-green-500' : ($monitor->status === 'down' ? 'bg-red-500' : 'bg-gray-400') }}"></span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $monitor->name }}</p>
                            <p class="text-xs text-gray-500">{{ $monitor->url }}</p>
                        </div>
                    </div>
                    <span class="text-sm capitalize text-gray-700">{{ $monitor->is_paused ? 'Paused' : $monitor->status }}</span>
                </li>
                @empty
                <li class="px-6 py-8 text-center text-sm text-gray-500">No monitors assigned to this status page.</li>
                @endforelse
            </ul>
        </div>

        @if($statusPage->incidents->isNotEmpty())
        <div class="bg-white shadow rounded-lg border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Active Incidents</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                @foreach($statusPage->incidents->where('status', '!=', 'resolved') as $incident)
                <li class="px-6 py-4">
                    <a href="{{ route('incidents.show', $incident) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">{{ $incident->title }}</a>
                    <p class="text-xs text-gray-500 mt-1">Impact: {{ $incident->impact }} &middot; {{ $incident->created_at->diffForHumans() }}</p>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</x-app-layout>
