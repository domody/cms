@extends('layouts.admin', [
    'title' => 'New user',
    'pageTitle' => 'New user',
    'pageDescription' => 'Someone who can log in to the CMS.',
    'breadcrumbs' => [
        ['label' => 'Admin'],
        ['label' => 'Users', 'url' => route('admin.users.index')],
        ['label' => 'New'],
    ],
])

@section('content')
    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
            @csrf

            <div class="grid gap-x-4 gap-y-5 sm:grid-cols-2">
                <x-field label="Name" name="name" required>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="input">
                </x-field>
                <x-field label="Email" name="email" required>
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" class="input">
                </x-field>
            </div>

            <x-field label="Access level" name="access_level" hint="Customers only reach the sites they belong to. Admins reach every site and this admin area.">
                <select name="access_level" required class="input">
                    @foreach (\App\Models\AccessLevel::all() as $level)
                        <option value="{{ $level }}" @selected(old('access_level', \App\Models\AccessLevel::CUSTOMER) === $level)>{{ \App\Models\AccessLevel::label($level) }}</option>
                    @endforeach
                </select>
            </x-field>

            <x-field label="Password" name="password" hint="A generated password is shown once after saving, so you can pass it on.">
                <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to generate one" class="input">
            </x-field>

            <div class="grid gap-x-4 gap-y-5 sm:grid-cols-2">
                <x-field label="Add to site" name="site_id">
                    <select name="site_id" class="input">
                        <option value="">None</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" @selected((int) old('site_id') === $site->id)>{{ $site->name }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field label="Role on that site" name="role">
                    <select name="role" class="input">
                        @foreach (\App\Models\SiteRole::all() as $role)
                            <option value="{{ $role }}" @selected(old('role', \App\Models\SiteRole::EDITOR) === $role)>{{ \App\Models\SiteRole::label($role) }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <x-button type="submit">Create user</x-button>
                <x-button variant="subtle" :href="route('admin.users.index')">Cancel</x-button>
            </div>
        </form>
    </x-card>
@endsection
