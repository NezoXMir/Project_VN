@props([
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->class(['flex items-center justify-between mb-4']) }}>
    <div>
        <h2 class="text-lg font-semibold">{{ $title }}</h2>
        @if ($subtitle)
            <p class="text-sm text-gray-500 mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($action))
        <div>{{ $action }}</div>
    @endif
</div>
