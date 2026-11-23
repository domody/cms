@props(['item', 'selected' => false, 'usageCount' => 0])

<div @class(['group overflow-hidden rounded-xl ring-1 transition', 'ring-2 ring-foreground' => $selected, 'ring-border hover:ring-foreground/30' => ! $selected])>
    <div class="relative">
        <a href="{{ request()->fullUrlWithQuery(['media' => $item->id]) }}" aria-label="Open details for {{ $item->original_name }}" @if ($selected) aria-current="true" @endif>
            <img src="{{ $item->variantUrl('thumb') }}" alt="{{ $item->alt }}" loading="lazy" class="aspect-video w-full bg-muted object-cover">
        </a>
        <input type="checkbox" name="ids[]" value="{{ $item->id }}" form="bulk-form" aria-label="Select {{ $item->original_name }}" class="checkbox absolute left-2 top-2">
        <span class="badge absolute bottom-2 right-2 bg-background/90 text-foreground">{{ $usageCount ? 'Used in '.$usageCount : 'Not used' }}</span>
    </div>
    <div class="px-3 py-2">
        <p class="truncate text-sm font-medium" title="{{ $item->original_name }}">
            {{ $item->original_name }}
        </p>
        <p class="text-xs text-secondary-foreground">
            @if ($item->width) {{ $item->width }}&times;{{ $item->height }} &middot; @endif
            {{ \App\Models\Media::formatBytes($item->size) }}
        </p>
    </div>
</div>
