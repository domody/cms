@props(['field', 'path', 'value' => []])

{{--
    Repeater field: an ordered list of rows bound to wire:model "$path". Three layouts:
    - grid: rows holding just an image (photo strips) are thumbnail tiles;
    - list: rows holding one other field are edited inline;
    - rows: rows with several fields collapse to a one-line summary (their first filled text).
      A row opens on its own when it is still empty or holds a validation error, so nothing that
      needs fixing hides.
--}}
@php
    $single = count($field['fields']) === 1;
    $layout = ! $single ? 'rows' : ($field['fields'][0]['type'] === 'image' ? 'grid' : 'list');
    $errorKeys = collect($errors->keys());
    $blankRow = \Illuminate\Support\Js::from(\App\Models\BlockDefinition::blankRow($field));

    $summary = function (array $row) use ($field): ?string {
        foreach ($field['fields'] as $sub) {
            $text = $row[$sub['name']] ?? null;
            $text = is_array($text) ? ($text['label'] ?? null) : $text;

            if (is_string($text) && trim($text) !== '') {
                return $text;
            }
        }

        return null;
    };
@endphp

<div {{ $attributes->class(['space-y-3']) }}>
    <div class="flex items-center gap-2">
        <span class="text-sm font-medium">
            {{ $field['label'] }}@if ($field['required'])<span class="ms-1 text-danger" aria-hidden="true">*</span>@endif
        </span>
        <x-badge>{{ count($value) }}</x-badge>
        <x-button
            variant="ghost"
            icon="add-01"
            class="ms-auto"
            x-on:click="$wire.$set('{{ $path }}', [...($wire.$get('{{ $path }}') ?? []), {{ $blankRow }}])"
        >Add item</x-button>
    </div>

    @if (count($value) === 0)
        <x-empty-state figure="basket" title="No items yet" class="rounded-lg border border-dashed">
            Add your first item to get started.
        </x-empty-state>
    @else
        <ol @class([
            'grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4' => $layout === 'grid',
            'space-y-2' => $layout === 'list',
            'divide-y overflow-hidden rounded-lg ring-1 ring-border' => $layout === 'rows',
        ])>
            @foreach ($value as $i => $row)
                @php
                    $row = is_array($row) ? $row : [];
                    $rowHasError = $errorKeys->contains(fn ($key) => str_starts_with($key, "$path.$i."));
                    $rowSummary = $layout === 'rows' ? $summary($row) : null;
                    $startsOpen = $layout === 'rows' && ($rowHasError || $rowSummary === null);
                @endphp

                @if ($layout === 'grid')
                    <li wire:key="{{ $path }}-row-{{ $i }}" class="group relative">
                        <x-block-field :field="$field['fields'][0]" :path="$path.'.'.$i.'.'.$field['fields'][0]['name']" :value="$row[$field['fields'][0]['name']] ?? null" :bare="true" wire:key="{{ $path }}-{{ $i }}-field" />
                        <div class="absolute end-1.5 top-1.5 flex rounded-lg bg-background/95 shadow-sm ring-1 ring-border transition-opacity sm:opacity-0 sm:group-hover:opacity-100 sm:focus-within:opacity-100">
                            <x-repeater-actions :path="$path" :index="$i" :last="$loop->last" :sideways="$layout === 'grid'" />
                        </div>
                    </li>
                @elseif ($layout === 'list')
                    <li wire:key="{{ $path }}-row-{{ $i }}" class="group flex items-start gap-2">
                        <span class="w-4 shrink-0 pt-2.5 text-xs tabular-nums text-muted-foreground">{{ $loop->iteration }}</span>
                        <x-block-field :field="$field['fields'][0]" :path="$path.'.'.$i.'.'.$field['fields'][0]['name']" :value="$row[$field['fields'][0]['name']] ?? null" :bare="true" class="min-w-0 flex-1" wire:key="{{ $path }}-{{ $i }}-field" />
                        <div class="flex shrink-0 items-center pt-0.5 text-muted-foreground transition-opacity sm:opacity-0 sm:group-hover:opacity-100 sm:focus-within:opacity-100">
                            <x-repeater-actions :path="$path" :index="$i" :last="$loop->last" :sideways="$layout === 'grid'" />
                        </div>
                    </li>
                @else
                    <li wire:key="{{ $path }}-row-{{ $i }}" x-data="{ open: @js($startsOpen) }" class="group">
                        <div class="flex items-center gap-2 py-1 pe-1.5 ps-3">
                            <span class="w-4 shrink-0 text-xs tabular-nums text-muted-foreground">{{ $loop->iteration }}</span>
                            <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" class="flex min-w-0 flex-1 items-center gap-2 rounded py-1.5 text-start text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <x-hugeicons-arrow-right-01 class="size-4 shrink-0 text-muted-foreground transition-transform" x-bind:class="open && 'rotate-90'" aria-hidden="true" />
                                <span @class(['truncate', 'font-medium' => $rowSummary !== null, 'text-muted-foreground' => $rowSummary === null])>{{ $rowSummary ?? 'New item' }}</span>
                                @if ($rowHasError)
                                    <x-badge tone="danger">Needs attention</x-badge>
                                @endif
                            </button>
                            <div class="flex shrink-0 items-center text-muted-foreground transition-opacity sm:opacity-0 sm:group-hover:opacity-100 sm:focus-within:opacity-100">
                                <x-repeater-actions :path="$path" :index="$i" :last="$loop->last" :sideways="$layout === 'grid'" />
                            </div>
                        </div>
                        <div x-show="open" @unless ($startsOpen) x-cloak @endunless class="pb-5 pe-4 ps-9">
                            <x-field-list :fields="$field['fields']" :path="$path.'.'.$i" :values="$row" />
                        </div>
                    </li>
                @endif
            @endforeach
        </ol>
    @endif

    @error($path)
        <span class="block text-xs font-medium text-danger">{{ $message }}</span>
    @enderror
</div>
