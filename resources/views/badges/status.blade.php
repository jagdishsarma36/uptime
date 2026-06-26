@php
    $color = match($status ?? 'up') {
        'up' => 'bg-green-100 text-green-800',
        'down' => 'bg-red-100 text-red-800',
        'paused' => 'bg-gray-100 text-gray-800',
        default => 'bg-yellow-100 text-yellow-800',
    };
    $dot = match($status ?? 'up') {
        'up' => 'bg-green-500',
        'down' => 'bg-red-500',
        'paused' => 'bg-gray-400',
        default => 'bg-yellow-500',
    };
@endphp
<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $color }} {{ $attributes->get('class') }}">
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
    {{ ucfirst($status ?? 'up') }}
</span>
