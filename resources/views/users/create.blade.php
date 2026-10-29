@extends('layouts.admin', [
    'title' => 'Add user',
    'pageTitle' => 'Add user',
    'pageDescription' => 'Give someone access to this site.',
    'breadcrumbs' => [
        ['label' => 'Users', 'url' => route('users.index')],
        ['label' => 'Add'],
    ],
])

@section('content')
    <form method="POST" action="{{ route('users.store') }}" class="max-w-xl space-y-4 rounded-lg border p-4">
        @csrf

        <label class="block text-sm font-medium">
            Name <span class="text-red-600">*</span>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="input mt-1">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>

        <label class="block text-sm font-medium">
            Email <span class="text-red-600">*</span>
            <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" class="input mt-1">
            <span class="mt-1 block text-xs font-normal text-secondary-foreground">If this email already has an account, they are added to this site with their existing details.</span>
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>

        <label class="block text-sm font-medium">
            Role
            <select name="role" required class="input mt-1">
                @foreach (\App\Models\SiteRole::all() as $role)
                    <option value="{{ $role }}" @selected(old('role', \App\Models\SiteRole::EDITOR) === $role)>{{ \App\Models\SiteRole::label($role) }}</option>
                @endforeach
            </select>
            @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>

        <p class="text-xs text-secondary-foreground">A new account gets a generated password, shown once after saving so you can pass it on.</p>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn">Add user</button>
            <a href="{{ route('users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
        </div>
    </form>
@endsection
