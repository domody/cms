@extends('layouts.base')

@section('base.content')

@php
    $breadcrumbs = $breadcrumbs ?? [];
    $hasHeading = isset($pageTitle) || isset($pageDescription);
@endphp

<div class="flex min-h-screen bg-muted">
    <x-sidebar :menu="$sidebarMenu" :current-site="$currentSite" :sites="$userSites" />

    {{-- content wrapper --}}
    <div class="flex h-screen min-w-0 flex-1 flex-col p-2">
        {{-- content body: the scroll container. Its header sticks, so page actions (Save) stay in reach on long pages.
             No backdrop-filter on the header: it would trap the fixed overlay of modals rendered in the actions. --}}
        <div class="flex min-w-auto flex-1 flex-col overflow-y-auto rounded-xl border bg-background" data-scroll-root>
            <header class="sticky top-0 z-30 h-14 w-full shrink-0 border-b bg-background px-4 sm:px-6">
                <div class="mx-auto flex h-full max-w-7xl items-center gap-4">
                <nav aria-label="Breadcrumb" class="min-w-0 flex-1">
                    <ol class="flex items-center gap-1.5 text-sm text-secondary-foreground">
                        @foreach ($breadcrumbs as $crumb)
                            @php($href = $crumb['url'] ?? $crumb['route'] ?? null)
                            <li @class(['flex min-w-0 items-center gap-1.5', 'shrink-0' => ! $loop->last])>
                                @if (! $loop->first)
                                    <x-hugeicons-arrow-right-01 class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                                @endif
                                @if ($href && ! $loop->last)
                                    <a href="{{ $href }}" class="rounded hover:text-foreground">{{ $crumb['label'] }}</a>
                                @else
                                    <span @class(['truncate', 'font-medium text-foreground' => $loop->last]) @if ($loop->last) aria-current="page" @endif>{{ $crumb['label'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>

                @if (View::hasSection('headerActions') || View::hasSection('pageActions'))
                    <div class="flex shrink-0 items-center gap-2">
                        @yield('headerActions')
                        @yield('pageActions')
                    </div>
                @endif
                </div>
            </header>

            {{-- Title & description --}}
            @if ($hasHeading)
                <div class="mx-auto w-full max-w-7xl px-4 pb-2 pt-6 sm:px-6">
                    @isset($pageTitle)
                        <h1 class="text-xl font-semibold tracking-tight">{{ $pageTitle }}</h1>
                    @endisset
                    @isset($pageDescription)
                        <p class="mt-1 text-sm text-secondary-foreground">{{ $pageDescription }}</p>
                    @endisset
                </div>
            @endif

            {{-- content --}}
            <main class="mx-auto w-full max-w-7xl flex-1 px-4 pb-10 pt-4 sm:px-6">
                @yield('content')
            </main>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('js/layout.admin.js') }}"></script>
@endpush
