<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $statusPage->title }} - Status</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-full bg-gray-50">
    <div class="max-w-3xl mx-auto px-4 py-12 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">{{ $teamName }}</p>
            <h1 class="mt-2 text-3xl font-bold text-gray-900">{{ $statusPage->title }}</h1>
            @if($statusPage->description)
            <p class="mt-3 text-base text-gray-600">{{ $statusPage->description }}</p>
            @endif
        </div>

        <div class="mb-10 rounded-lg border p-4 text-center
            {{ $overallStatus === 'up' ? 'bg-green-50 border-green-200' : ($overallStatus === 'degraded' ? 'bg-yellow-50 border-yellow-200' : 'bg-red-50 border-red-200') }}">
            <div class="flex items-center justify-center gap-2">
                <span class="h-3 w-3 rounded-full {{ $overallStatus === 'up' ? 'bg-green-500' : ($overallStatus === 'degraded' ? 'bg-yellow-500' : 'bg-red-500') }}"></span>
                <span class="text-lg font-semibold {{ $overallStatus === 'up' ? 'text-green-800' : ($overallStatus === 'degraded' ? 'text-yellow-800' : 'text-red-800') }}">
                    {{ $overallStatus === 'up' ? 'All Systems Operational' : ($overallStatus === 'degraded' ? 'Partial System Outage' : 'Major Outage') }}
                </span>
            </div>
        </div>

        @if($activeIncidents->isNotEmpty())
        <div class="mb-10 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Active Incidents</h2>
            @foreach($activeIncidents as $incident)
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                        {{ ucfirst($incident->status) }}
                    </span>
                    <span class="text-xs text-gray-500">Impact: {{ $incident->impact }}</span>
                </div>
                <h3 class="text-sm font-semibold text-gray-900">{{ $incident->title }}</h3>
                @if($incident->description)
                <p class="mt-1 text-sm text-gray-700">{{ $incident->description }}</p>
                @endif
                @if($incident->updates->isNotEmpty())
                <div class="mt-3 space-y-2 border-t border-yellow-200 pt-3">
                    @foreach($incident->updates->take(3) as $update)
                    <div class="text-xs text-gray-600">
                        <span class="font-medium">{{ ucfirst($update->status) }}</span> - {{ $update->message }}
                        <span class="text-gray-400 ml-1">{{ $update->created_at->diffForHumans() }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        <div class="space-y-3">
            <h2 class="text-lg font-semibold text-gray-900">Monitors</h2>
            @foreach($monitors as $monitor)
            <div class="rounded-lg border border-gray-200 bg-white p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="h-2.5 w-2.5 rounded-full {{ $monitor->status === 'up' ? 'bg-green-500' : ($monitor->status === 'down' ? 'bg-red-500' : 'bg-gray-400') }}"></span>
                    <span class="text-sm font-medium text-gray-900">{{ $monitor->name }}</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">{{ $monitor->uptime_24h ?? '100.00' }}%</span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                        {{ $monitor->status === 'up' ? 'bg-green-100 text-green-800' : ($monitor->status === 'down' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                        {{ $monitor->is_paused ? 'Paused' : ucfirst($monitor->status) }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-12 text-center text-xs text-gray-400">
            Powered by <span class="font-medium">UptimeGuard</span>
        </div>
    </div>
</body>
</html>
