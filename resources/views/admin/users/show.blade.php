@extends('layouts.admin', [
    'title' => $user->name,
    'pageTitle' => $user->name,
    'pageDescription' => $user->email,
    'breadcrumbs' => [
        ['label' => 'Admin'],
        ['label' => 'Users', 'url' => route('admin.users.index')],
        ['label' => $user->name],
    ],
])

@php
    $isSelf = $user->is(auth()->user());
@endphp

@section('content')
    <div class="max-w-3xl space-y-4">
        @if (session('shown_password'))
            <x-one-time-secret :title="'Password for '.$user->email" :value="session('shown_password')" />
        @endif

        @if ($user->isDisabled())
            <x-callout tone="danger">This user is disabled: they can't log in and their API tokens don't work.</x-callout>
        @endif

        <x-card title="Details">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
                @csrf
                <div class="grid gap-x-4 gap-y-5 sm:grid-cols-2">
                    <x-field label="Name" name="name" required>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="input">
                    </x-field>
                    <x-field label="Email" name="email" required>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" class="input">
                    </x-field>
                </div>
                @if ($isSelf)
                    <x-field as="div" label="Access level">
                        <input type="hidden" name="access_level" value="{{ $user->access_level }}">
                        <p class="flex items-center gap-2 text-sm">
                            {!! \App\Models\AccessLevel::badge($user->access_level) !!}
                            <span class="text-secondary-foreground">You can't change your own.</span>
                        </p>
                    </x-field>
                @else
                    <x-field label="Access level" name="access_level">
                        <select name="access_level" required class="input">
                            @foreach (\App\Models\AccessLevel::all() as $level)
                                <option value="{{ $level }}" @selected(old('access_level', $user->access_level) === $level)>{{ \App\Models\AccessLevel::label($level) }}</option>
                            @endforeach
                        </select>
                    </x-field>
                @endif
                <x-button type="submit">Save details</x-button>
            </form>
        </x-card>

        <x-card title="Sites" :flush="true">
            @forelse ($user->sites as $site)
                <div class="flex items-center justify-between border-b px-5 py-3 text-sm last:border-b-0">
                    <a href="{{ route('admin.sites.show', $site->slug) }}" class="font-medium hover:underline">{{ $site->name }}</a>
                    {!! \App\Models\SiteRole::badge($site->pivot->role) !!}
                </div>
            @empty
                <x-empty-state figure="plug" title="No sites yet">
                    Give this user access to a site to get them started.
                </x-empty-state>
            @endforelse
        </x-card>

        <x-card title="Reset password">
            <form method="POST" action="{{ route('admin.users.password', $user) }}" class="flex flex-wrap items-end gap-3" data-confirm="Set a new password for {{ $user->name }}?">
                @csrf
                <x-field label="New password" name="password" class="min-w-48 flex-1">
                    <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to generate one" class="input">
                </x-field>
                <x-button type="submit" variant="outline" class="h-[2.375rem]">Reset password</x-button>
            </form>
        </x-card>

        @unless ($isSelf)
            <x-card title="Danger zone" tone="danger">
                <div class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('admin.users.disabled', $user) }}" data-confirm="{{ $user->isDisabled() ? 'Enable' : 'Disable' }} {{ $user->name }}?">
                        @csrf
                        <x-button type="submit" variant="outline">{{ $user->isDisabled() ? 'Enable user' : 'Disable user' }}</x-button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Permanently delete {{ $user->name }} and their API tokens? This can't be undone.">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger">Delete user</x-button>
                    </form>
                </div>
            </x-card>
        @endunless
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin.users.show.js') }}"></script>
@endpush
