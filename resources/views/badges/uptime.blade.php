<span class="inline-flex items-center
    {{ ($percentage ?? 100) >= 99 ? 'text-green-700' : (($percentage ?? 100) >= 95 ? 'text-yellow-700' : 'text-red-700') }}
    {{ $attributes->get('class') }}">
    {{ number_format($percentage ?? 100, 2) }}%
</span>
