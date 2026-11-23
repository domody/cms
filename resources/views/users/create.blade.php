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
    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-5">
            @csrf

            <x-field label="Name" name="name" required>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="input">
            </x-field>

            <x-field label="Email" name="email" required hint="If this email already has an account, they are added to this site with their existing details.">
                <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" class="input">
            </x-field>

            <x-field label="Role" name="role" hint="A new account gets a generated password, shown once after saving so you can pass it on.">
                <select name="role" required class="input">
                    @foreach (\App\Models\SiteRole::all() as $role)
                        <option value="{{ $role }}" @selected(old('role', \App\Models\SiteRole::EDITOR) === $role)>{{ \App\Models\SiteRole::label($role) }}</option>
                    @endforeach
                </select>
            </x-field>

            <div class="flex items-center gap-2 pt-1">
                <x-button type="submit">Add user</x-button>
                <x-button variant="subtle" :href="route('users.index')">Cancel</x-button>
            </div>
        </form>
    </x-card>
@endsection
