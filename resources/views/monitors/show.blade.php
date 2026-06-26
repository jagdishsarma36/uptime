<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-gray-800">{{ $monitor->name }}</h2>
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium
                    {{ $monitor->is_paused ? 'bg-gray-100 text-gray-800' : ($monitor->status === 'up' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $monitor->is_paused ? 'bg-gray-400' : ($monitor->status === 'up' ? 'bg-green-500' : 'bg-red-500') }}"></span>
                    {{ $monitor->is_paused ? 'Paused' : ucfirst($monitor->status) }}
                </span>
            </div>
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('monitors.check-now', $monitor) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                        Check Now
                    </button>
                </form>
                <form method="POST" action="{{ route('monitors.toggle-pause', $monitor) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-md {{ $monitor->is_paused ? 'bg-green-600 hover:bg-green-500' : 'bg-yellow-600 hover:bg-yellow-500' }} px-3 py-2 text-sm font-semibold text-white shadow-sm">
                        {{ $monitor->is_paused ? 'Resume' : 'Pause' }}
                    </button>
                </form>
                <a href="{{ route('monitors.edit', $monitor) }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Edit
                </a>
                <form method="POST" action="{{ route('monitors.destroy', $monitor) }}" onsubmit="return confirm('Are you sure you want to delete this monitor? This action cannot be undone.')">
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
                    <dt class="text-sm font-medium text-gray-500">URL</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $monitor->url }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                    <dd class="mt-1 text-sm text-gray-900 uppercase">{{ $monitor->type }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Method</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $monitor->method }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Group</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $monitor->group->name ?? 'None' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Check Interval</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $monitor->check_interval_seconds }}s</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Checked</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $monitor->last_checked_at ? $monitor->last_checked_at->diffForHumans() : 'Never' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Response</dt>
                    <dd class="mt-1 text-sm {{ ($monitor->last_response_time_ms ?? 0) > 1000 ? 'text-red-600' : (($monitor->last_response_time_ms ?? 0) > 500 ? 'text-yellow-600' : 'text-green-600') }}">
                        {{ $monitor->last_response_time_ms ? $monitor->last_response_time_ms . 'ms' : '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Message</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $monitor->last_message ?? '-' }}</dd>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
                <dt class="text-sm font-medium text-gray-500">Uptime (24h)</dt>
                <dd class="mt-2 text-3xl font-bold {{ $uptime24 >= 99 ? 'text-green-600' : ($uptime24 >= 95 ? 'text-yellow-600' : 'text-red-600') }}">{{ number_format($uptime24, 2) }}%</dd>
            </div>
            <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
                <dt class="text-sm font-medium text-gray-500">Uptime (7d)</dt>
                <dd class="mt-2 text-3xl font-bold {{ $uptime7d >= 99 ? 'text-green-600' : ($uptime7d >= 95 ? 'text-yellow-600' : 'text-red-600') }}">{{ number_format($uptime7d, 2) }}%</dd>
            </div>
            <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
                <dt class="text-sm font-medium text-gray-500">Uptime (30d)</dt>
                <dd class="mt-2 text-3xl font-bold {{ $uptime30d >= 99 ? 'text-green-600' : ($uptime30d >= 95 ? 'text-yellow-600' : 'text-red-600') }}">{{ number_format($uptime30d, 2) }}%</dd>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Response Time Stats</h3>
            <div class="grid grid-cols-3 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Average</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $responseStats['avg'] ?? '-' }}ms</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Minimum</dt>
                    <dd class="mt-1 text-lg font-semibold text-green-600">{{ $responseStats['min'] ?? '-' }}ms</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Maximum</dt>
                    <dd class="mt-1 text-lg font-semibold text-red-600">{{ $responseStats['max'] ?? '-' }}ms</dd>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Response Time (Last 50 Checks)</h3>
            <canvas id="responseTimeChart" height="100"></canvas>
        </div>

        @if($latestSsl)
        <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">SSL Certificate</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Domain</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $latestSsl->domain }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Expiry Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $latestSsl->ssl_expiry_date->format('M d, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Days Left</dt>
                    <dd class="mt-1 text-sm font-medium {{ $latestSsl->days_left <= 7 ? 'text-red-600' : ($latestSsl->days_left <= 20 ? 'text-yellow-600' : 'text-green-600') }}">{{ $latestSsl->days_left }} days</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Issuer</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $latestSsl->issuer ?? '-' }}</dd>
                </div>
            </div>
        </div>
        @endif

        @if($latestDomain)
        <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Domain Registration</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Domain</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $latestDomain->domain }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Expiry Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $latestDomain->expiry_date?->format('M d, Y') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Days Left</dt>
                    <dd class="mt-1 text-sm font-medium {{ $latestDomain->days_left <= 7 ? 'text-red-600' : ($latestDomain->days_left <= 30 ? 'text-yellow-600' : 'text-green-600') }}">{{ $latestDomain->days_left }} days</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Registrar</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $latestDomain->registrar ?? '-' }}</dd>
                </div>
            </div>
        </div>
        @endif

        <div class="bg-white shadow rounded-lg border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Recent Checks</h3>
                <a href="{{ route('monitors.logs', $monitor) }}" class="text-sm text-indigo-600 hover:text-indigo-900">View All Logs &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HTTP Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Response Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Checked At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($checkResults as $result)
                        <tr class="hover:bg-gray-50">
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
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ $result->message ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $result->checked_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No check results yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const labels = @js($checkResults->pluck('checked_at')->reverse()->map(fn($d) => $d->format('H:i'))->values());
            const data = @js($checkResults->pluck('response_time_ms')->reverse()->values());
            const colors = data.map(v => v > 1000 ? '#ef4444' : (v > 500 ? '#eab308' : '#22c55e'));

            new Chart(document.getElementById('responseTimeChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Response Time (ms)',
                        data: data,
                        backgroundColor: colors,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'ms' } },
                        x: { ticks: { maxTicksLimit: 10 } }
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
