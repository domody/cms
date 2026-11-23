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
    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('admin.sites.store') }}" class="space-y-5">
            @csrf

            <x-field label="Name" name="name" required>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="input">
            </x-field>

            <x-field label="Slug" name="slug" hint="The first part of the site's CMS address (/slug/dashboard). It can't be changed later.">
                <input type="text" name="slug" value="{{ old('slug') }}" maxlength="64" placeholder="Generated from the name" class="input font-mono text-[13px]">
            </x-field>

            <div class="flex items-center gap-2 pt-1">
                <x-button type="submit">Create site</x-button>
                <x-button variant="subtle" :href="route('admin.sites.index')">Cancel</x-button>
            </div>
        </form>
    </x-card>
@endsection
