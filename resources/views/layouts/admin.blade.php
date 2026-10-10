@extends('layouts.base')

@section('base.content')

<div class="flex min-h-screen">
    <x-sidebar :menu="$sidebarMenu" />

    {{-- content wrapper --}}
    <div class="flex flex-1 flex-col min-w-0 min-h-screen">
        <div class="px-4 py-4">
            {{-- page header / title --}}
        </div>

        <div class="flex-1 px-4">
            @yield('content')
        </div>

        <footer class="px-4 py-4 text-sm text-gray-500">
            {{-- footer --}}
        </footer>
    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('js/layout.admin.js') }}"></script>
@endpush
