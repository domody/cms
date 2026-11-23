@extends('layouts.admin', [
    'title' => $page->title,
    'pageTitle' => $page->title,
    'pageDescription' => '/'.$page->slug,
    'breadcrumbs' => [
        ['label' => 'Pages', 'route' => route('pages.index')],
        ['label' => $page->title],
    ],
])

@section('pageActions')
    <x-button icon="edit-02" :href="route('pages.edit', $page)">Edit</x-button>
@endsection

@section('content')
    <div class="max-w-4xl space-y-4">
        @if ($page->status !== \App\Models\PageStatus::PUBLISHED)
            <x-callout tone="warning">This page is {{ strtolower(\App\Models\PageStatus::label($page->status)) }}, so the API doesn't serve it until it is published.</x-callout>
        @endif

        @forelse ($page->blocks as $block)
            @php $definition = $definitions->get($block->block_key); @endphp
            <x-card
                :title="$loop->iteration.'. '.($definition?->label ?? $block->block_key)"
                :class="$block->is_visible ? '' : 'opacity-60'"
            >
                <x-slot:actions>
                    @unless ($block->is_visible)
                        <x-badge>Hidden</x-badge>
                    @endunless
                    <code class="text-xs text-muted-foreground">{{ $block->block_key }} · v{{ $block->schema_version }}</code>
                </x-slot:actions>

                <div class="space-y-4">
                    @if ($definition === null)
                        <x-callout tone="warning">No definition synced for this block type.</x-callout>
                    @endif
                    @foreach ($definition?->fields() ?? [] as $field)
                        <x-block-preview :field="$field" :value="$field['collection'] ?? ($block->content[$field['name']] ?? null)" />
                    @endforeach
                </div>
            </x-card>
        @empty
            <x-empty-state figure="exploded" title="No blocks yet">
                Blocks appear once your frontend declares them for this page.
            </x-empty-state>
        @endforelse

        <details class="rounded-xl ring-1 ring-border">
            <summary class="cursor-pointer px-5 py-3 text-sm font-medium">API response <span class="font-normal text-secondary-foreground">(what the frontend receives)</span></summary>
            <pre class="overflow-x-auto border-t bg-muted px-5 py-4 text-xs">{{ json_encode(['data' => $apiPayload], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
    </div>
@endsection
