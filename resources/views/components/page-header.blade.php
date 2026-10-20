@props(['title', 'subtitle' => ''])

<div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 mb-5">
    <div class="min-w-0">
        <h1 class="text-lg font-semibold">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-sm" style="color: var(--text-muted)">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
</div>
