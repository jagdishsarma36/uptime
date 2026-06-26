<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Check Logs: {{ $monitor->name }}</h2>
            <a href="{{ route('monitors.show', $monitor) }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Back to Monitor</a>
        </div>
    </x-slot>

    <div class="bg-white shadow rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HTTP Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Response Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($results as $result)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $result->checked_at->format('M d, Y H:i:s') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full {{ $result->status === 'up' ? 'bg-green-500' : 'bg-red-500' }}"></span>
                            <span class="text-sm capitalize {{ $result->status === 'up' ? 'text-green-700' : 'text-red-700' }}">{{ $result->status }}</span>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $result->http_code ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm {{ ($result->response_time_ms ?? 0) > 1000 ? 'text-red-600' : (($result->response_time_ms ?? 0) > 500 ? 'text-yellow-600' : 'text-green-600') }}">
                        {{ $result->response_time_ms ? $result->response_time_ms . 'ms' : '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-md truncate">{{ $result->message ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <p class="text-sm text-gray-500">No check results found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $results->links() }}
    </div>
</x-app-layout>
