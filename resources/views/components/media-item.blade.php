@props(['item', 'selected' => false, 'usageCount' => 0])

<div class="overflow-hidden rounded-lg border p-1">
    <div class="relative rounded-md overflow-hidden ring ring-ring">
        <a href="{{ request()->fullUrlWithQuery(['media' => $item->id]) }}" aria-label="Open details for {{ $item->original_name }}" @if ($selected) aria-current="true" @endif>
            <img src="{{ $item->variantUrl('thumb') }}" alt="{{ $item->alt }}" loading="lazy" class="aspect-video w-full bg-muted object-cover">
        </a>
        <input type="checkbox" name="ids[]" value="{{ $item->id }}" form="bulk-form" aria-label="Select {{ $item->original_name }}" class="checkbox absolute left-1 top-1">
        <span class="absolute bottom-1 right-1 rounded-full bg-white/90 px-2 py-0.5 text-xs font-medium">{{ $usageCount ? 'Used in '.$usageCount : 'Not used' }}</span>
    </div>
    <div class="p-2">
        <p class="truncate text-sm font-medium" title="{{ $item->original_name }}">
            {{ $item->original_name }}
        </p>
        <p class="text-xs text-muted-foreground">
            @if ($item->width) {{ $item->width }}&times;{{ $item->height }} @endif
            &middot; {{ \App\Models\Media::formatBytes($item->size) }}
        </p>
    </div>
</div>
