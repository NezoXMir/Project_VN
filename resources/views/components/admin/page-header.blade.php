@props([
    'title',
    'subtitle' => null,
])

<div class="flex items-start justify-between mb-6 gap-4 flex-wrap">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ $title }}</h2>
        @if ($subtitle)
            <p class="text-sm text-gray-500 mt-1">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex items-center gap-2 flex-wrap">
            {{ $actions }}
        </div>
    @endisset
</div>
