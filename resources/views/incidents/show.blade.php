<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-gray-800">{{ $incident->title }}</h2>
                @php
                    $statusColors = [
                        'investigating' => 'bg-yellow-100 text-yellow-800',
                        'identified' => 'bg-orange-100 text-orange-800',
                        'monitoring' => 'bg-blue-100 text-blue-800',
                        'resolved' => 'bg-green-100 text-green-800',
                    ];
                @endphp
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$incident->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($incident->status) }}
                </span>
            </div>
            <a href="{{ route('incidents.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Back to Incidents</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ $incident->status }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Impact</dt>
                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ $incident->impact }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status Page</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $incident->statusPage->title ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Created</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $incident->created_at->format('M d, Y H:i') }}</dd>
                </div>
            </div>
            @if($incident->description)
            <div class="mt-4 pt-4 border-t border-gray-200">
                <dt class="text-sm font-medium text-gray-500">Description</dt>
                <dd class="mt-1 text-sm text-gray-700">{{ $incident->description }}</dd>
            </div>
            @endif
            @if($incident->monitors->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-gray-200">
                <dt class="text-sm font-medium text-gray-500 mb-2">Affected Monitors</dt>
                <div class="flex flex-wrap gap-2">
                    @foreach($incident->monitors as $monitor)
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-800">{{ $monitor->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="bg-white shadow rounded-lg border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Timeline</h3>
            </div>
            <div class="px-6 py-5 space-y-4">
                @forelse($incident->updates as $update)
                <div class="flex gap-4 {{ !$loop->last ? 'pb-4 border-b border-gray-100' : '' }}">
                    <div class="flex-shrink-0 mt-1">
                        <span class="h-2.5 w-2.5 rounded-full {{ $update->status === 'resolved' ? 'bg-green-500' : ($update->status === 'investigating' ? 'bg-yellow-500' : 'bg-blue-500') }}"></span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800">{{ ucfirst($update->status) }}</span>
                            <span class="text-xs text-gray-500">{{ $update->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-700">{{ $update->message }}</p>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center py-4">No updates yet.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white shadow rounded-lg border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Add Update</h3>
            </div>
            <form method="POST" action="{{ route('incidents.update-status', $incident) }}" class="px-6 py-5 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        <option value="investigating">Investigating</option>
                        <option value="identified">Identified</option>
                        <option value="monitoring">Monitoring</option>
                        <option value="resolved">Resolved</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="message" value="Message" />
                    <textarea id="message" name="message" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>{{ old('message') }}</textarea>
                    <x-input-error :messages="$errors->get('message')" class="mt-1" />
                </div>
                <div class="flex justify-end">
                    <x-primary-button>Post Update</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
