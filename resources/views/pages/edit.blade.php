@extends('layouts.admin')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <div>
            <a href="{{ route('pages.index') }}" class="text-sm text-gray-500 hover:text-gray-900">&larr; Pages</a>
            <h1 class="text-xl font-semibold text-gray-900">Edit /{{ $page->slug }}</h1>
        </div>
        <a href="{{ route('pages.show', $page) }}" class="text-sm text-gray-600 hover:text-gray-900">View page</a>
    </div>

    <livewire:page-editor :page="$page" />
@endsection
