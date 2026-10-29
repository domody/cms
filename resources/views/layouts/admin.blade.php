@extends('layouts.base')

@section('base.content')

<div class="flex min-h-screen bg-muted">
    <x-sidebar :menu="$sidebarMenu" :current-site="$currentSite" :sites="$userSites" />

    {{-- content wrapper --}}
    <div class="flex flex-1 flex-col min-w-0 h-screen p-2">
        {{-- content body --}}
        <div class="flex flex-1 flex-col min-w-auto bg-background border rounded-xl overflow-y-auto">
            {{-- header --}}
            <div class="w-full h-14 shrink-0 flex items-center justify-between px-4 border-b">
                {{-- breadcrumbs --}}
                @php $breadcrumbs = $breadcrumbs ?? []; @endphp
                <div class="text-sm text-secondary-foreground">
                    @if (count($breadcrumbs) > 0)
                        {{ collect($breadcrumbs)->pluck('label')->implode(' / ') }}
                    @endif
                </div>

                @hasSection('headerActions')
                    <div class="flex items-center gap-2">
                        @yield('headerActions')
                    </div>
                @endif
            </div>

            {{-- Title, desc, & page actions --}}
            @if (isset($pageTitle) || isset($pageDescription) || View::hasSection('pageActions'))
            <div class="flex flex-col gap-2 px-4 py-4 sm:flex-row sm:items-center">
                <div class="flex-1 min-w-0">
                    @isset($pageTitle)
                        <h1 class="text-xl font-bold">{{ $pageTitle }}</h1>
                    @endisset
                    @isset($pageDescription)
                        <div class="mt-1 text-sm text-secondary-foreground">{{ $pageDescription }}</div>
                    @endisset
                </div>

                @hasSection('pageActions')
                    <div class="flex items-center gap-2 sm:ml-auto">
                        @yield('pageActions')
                    </div>
                @endif
            </div>
            @endif

            {{-- content --}}
            <div @class(['flex-1 px-4', 'pt-4' => ! (isset($pageTitle) || isset($pageDescription) || View::hasSection('pageActions'))])>
                @yield('content')
            </div>

            <footer class="px-4 py-4 text-sm text-gray-500">
                {{-- footer --}}
            </footer>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('js/layout.admin.js') }}"></script>
@endpush
