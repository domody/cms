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
    <form method="POST" action="{{ route('admin.users.store') }}" class="max-w-xl space-y-4 rounded-lg border p-4">
        @csrf

        <label class="block text-sm font-medium">
            Name <span class="text-red-600">*</span>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="input mt-1">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>

        <label class="block text-sm font-medium">
            Email <span class="text-red-600">*</span>
            <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" class="input mt-1">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>

        <label class="block text-sm font-medium">
            Access level
            <select name="access_level" required class="input mt-1">
                @foreach (\App\Models\AccessLevel::all() as $level)
                    <option value="{{ $level }}" @selected(old('access_level', \App\Models\AccessLevel::CUSTOMER) === $level)>{{ \App\Models\AccessLevel::label($level) }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs font-normal text-secondary-foreground">Customers only reach the sites they belong to. Admins reach every site and this admin area.</span>
            @error('access_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>

        <label class="block text-sm font-medium">
            Password
            <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to generate one" class="input mt-1">
            <span class="mt-1 block text-xs font-normal text-secondary-foreground">A generated password is shown once after saving, so you can pass it on.</span>
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm font-medium">
                Add to site
                <select name="site_id" class="input mt-1">
                    <option value="">None</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}" @selected((int) old('site_id') === $site->id)>{{ $site->name }}</option>
                    @endforeach
                </select>
                @error('site_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>
            <label class="block text-sm font-medium">
                Role on that site
                <select name="role" class="input mt-1">
                    @foreach (\App\Models\SiteRole::all() as $role)
                        <option value="{{ $role }}" @selected(old('role', \App\Models\SiteRole::EDITOR) === $role)>{{ \App\Models\SiteRole::label($role) }}</option>
                    @endforeach
                </select>
                @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn">Create user</button>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
        </div>
    </form>
@endsection
