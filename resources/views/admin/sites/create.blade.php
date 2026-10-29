@extends('layouts.admin', [
    'title' => 'New site',
    'pageTitle' => 'New site',
    'pageDescription' => 'A client\'s website. Add its users and API token once it exists.',
    'breadcrumbs' => [
        ['label' => 'Admin'],
        ['label' => 'Sites', 'url' => route('admin.sites.index')],
        ['label' => 'New'],
    ],
])

@section('content')
    <form method="POST" action="{{ route('admin.sites.store') }}" class="max-w-xl space-y-4 rounded-lg border p-4">
        @csrf

        <label class="block text-sm font-medium">
            Name <span class="text-red-600">*</span>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="input mt-1">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>

        <label class="block text-sm font-medium">
            Slug
            <input type="text" name="slug" value="{{ old('slug') }}" maxlength="64" placeholder="Generated from the name" class="input mt-1">
            <span class="mt-1 block text-xs font-normal text-secondary-foreground">The first part of the site's CMS address (/slug/dashboard). It can't be changed later.</span>
            @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn">Create site</button>
            <a href="{{ route('admin.sites.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
        </div>
    </form>
@endsection
