@extends('layouts.admin')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <div>
            <a href="{{ route('pages.index') }}" class="text-sm text-gray-500 hover:text-gray-900">&larr; Pages</a>
            <h1 class="text-xl font-semibold text-gray-900">{{ $page->title }}</h1>
            <p class="text-sm text-gray-500">
                /{{ $page->slug }} &middot; {{ ucfirst($page->status) }}
                @if ($page->status !== 'published')
                    <span class="text-amber-700">(not served by the API until published)</span>
                @endif
            </p>
        </div>
        <a href="{{ route('pages.edit', $page) }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Edit</a>
    </div>

    <div class="space-y-3">
        @forelse ($page->blocks as $block)
            @php $definition = $definitions->get($block->block_key); @endphp
            <section class="rounded-lg border bg-white {{ $block->is_visible ? 'border-gray-200' : 'border-dashed border-gray-300 opacity-60' }}">
                <header class="flex items-center justify-between border-b border-gray-100 px-4 py-2 text-xs text-gray-500">
                    <span class="font-medium uppercase tracking-wide">
                        {{ $loop->iteration }}. {{ $definition?->label ?? $block->block_key }}
                        <code class="ms-1 normal-case text-gray-400">{{ $block->block_key }}</code>
                    </span>
                    <span>
                        @unless ($block->is_visible) Hidden &middot; @endunless
                        schema v{{ $block->schema_version }}
                    </span>
                </header>
                <div class="space-y-3 px-4 py-4">
                    @if ($definition === null)
                        <p class="text-sm text-amber-700">No definition synced for this block type.</p>
                    @endif
                    @foreach ($definition?->fields() ?? [] as $field)
                        <x-block-preview :field="$field" :value="$block->content[$field['name']] ?? null" />
                    @endforeach
                </div>
            </section>
        @empty
            <p class="text-sm text-gray-500">This page has no blocks.</p>
        @endforelse
    </div>

    <details class="mt-6 rounded-lg border border-gray-200 bg-white">
        <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-gray-700">API response (what the frontend receives)</summary>
        <pre class="overflow-x-auto border-t border-gray-100 bg-gray-50 px-4 py-3 text-xs text-gray-800">{{ json_encode(['data' => $apiPayload], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
    </details>
@endsection
