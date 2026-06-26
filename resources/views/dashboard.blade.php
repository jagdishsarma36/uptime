<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
    </x-slot>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Monitors</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ $stats['total'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg border border-green-200">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-3 w-3 rounded-full bg-green-500"></div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Up</dt>
                            <dd class="text-lg font-semibold text-green-600">{{ $stats['up'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg border border-red-200">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-3 w-3 rounded-full bg-red-500"></div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Down</dt>
                            <dd class="text-lg font-semibold text-red-600">{{ $stats['down'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-3 w-3 rounded-full bg-gray-400"></div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Paused</dt>
                            <dd class="text-lg font-semibold text-gray-600">{{ $stats['paused'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg border border-indigo-200">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg Uptime (24h)</dt>
                            <dd class="text-lg font-semibold {{ $stats['uptime_24h'] >= 99 ? 'text-green-600' : ($stats['uptime_24h'] >= 95 ? 'text-yellow-600' : 'text-red-600') }}">{{ $stats['uptime_24h'] }}%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monitors Table -->
    <div class="bg-white shadow rounded-lg border border-gray-200 mb-8">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium leading-6 text-gray-900">All Monitors</h3>
                <a href="{{ route('monitors.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                    Add Monitor
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Response</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Check</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($monitors as $monitor)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full {{ $monitor->status === 'up' ? 'bg-green-500' : ($monitor->status === 'down' ? 'bg-red-500' : ($monitor->is_paused ? 'bg-gray-400' : 'bg-yellow-500')) }}"></span>
                                <span class="text-sm text-gray-700 capitalize">{{ $monitor->is_paused ? 'paused' : $monitor->status }}</span>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('monitors.show', $monitor) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">{{ $monitor->name }}</a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-500 max-w-xs truncate block">{{ $monitor->url }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($monitor->last_response_time_ms)
                            <span class="text-sm text-gray-700 {{ $monitor->last_response_time_ms > 1000 ? 'text-red-600' : ($monitor->last_response_time_ms > 500 ? 'text-yellow-600' : 'text-green-600') }}">{{ $monitor->last_response_time_ms }}ms</span>
                            @else
                            <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-500">{{ $monitor->last_checked_at ? $monitor->last_checked_at->diffForHumans() : 'Never' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <form method="POST" action="{{ route('monitors.check-now', $monitor) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-indigo-600 hover:text-indigo-900" title="Check Now">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('monitors.toggle-pause', $monitor) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-yellow-600 hover:text-yellow-900" title="{{ $monitor->is_paused ? 'Resume' : 'Pause' }}">
                                    @if($monitor->is_paused)
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" /></svg>
                                    @else
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" /></svg>
                                    @endif
                                </button>
                            </form>
                            <a href="{{ route('monitors.edit', $monitor) }}" class="text-gray-600 hover:text-gray-900" title="Edit">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 9.563C9 9.252 9.252 9 9.563 9h.874c.311 0 .563.252.563.563v4.874c0 .311-.252.563-.563.563h-.874A.562.562 0 019 14.437V9.564z" /></svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900">No monitors</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating a new monitor.</p>
                            <div class="mt-6">
                                <a href="{{ route('monitors.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                                    New Monitor
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SSL & Domain Warnings -->
    @if($sslWarnings->isNotEmpty() || $domainWarnings->isNotEmpty())
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        @if($sslWarnings->isNotEmpty())
        <div class="bg-white shadow rounded-lg border border-yellow-200">
            <div class="px-4 py-5 sm:px-6 border-b border-yellow-200 bg-yellow-50">
                <h3 class="text-lg font-medium text-yellow-800">SSL Certificate Warnings</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                @foreach($sslWarnings as $ssl)
                <li class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $ssl->monitor->name }}</p>
                        <p class="text-sm text-gray-500">{{ $ssl->domain }} - {{ $ssl->days_left }} days left</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $ssl->days_left <= 7 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ $ssl->ssl_expiry_date->format('M d, Y') }}
                    </span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($domainWarnings->isNotEmpty())
        <div class="bg-white shadow rounded-lg border border-yellow-200">
            <div class="px-4 py-5 sm:px-6 border-b border-yellow-200 bg-yellow-50">
                <h3 class="text-lg font-medium text-yellow-800">Domain Expiry Warnings</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                @foreach($domainWarnings as $domain)
                <li class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $domain->monitor->name }}</p>
                        <p class="text-sm text-gray-500">{{ $domain->domain }} - {{ $domain->days_left }} days left</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $domain->days_left <= 7 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ $domain->expiry_date->format('M d, Y') }}
                    </span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif
</x-app-layout>
