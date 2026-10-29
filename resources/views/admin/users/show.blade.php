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
    <div class="max-w-3xl space-y-6 pb-8">
        @if (session('shown_password'))
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm">
                <p class="font-semibold text-amber-900">Password for {{ $user->email }}</p>
                <p class="mt-1 text-amber-800">Copy it now and pass it on: it is only shown once.</p>
                <input type="text" readonly value="{{ session('shown_password') }}" onfocus="this.select()" class="mt-2 block w-full rounded border border-amber-300 bg-white px-3 py-2 font-mono text-xs">
            </div>
        @endif

        @if ($user->isDisabled())
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                This user is disabled: they can't log in and their API tokens don't work.
            </div>
        @endif

        {{-- Details --}}
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4 rounded-lg border p-4">
            @csrf
            <h2 class="text-sm font-semibold">Details</h2>

            <label class="block text-sm font-medium">
                Name <span class="text-red-600">*</span>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="input mt-1">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>
            <label class="block text-sm font-medium">
                Email <span class="text-red-600">*</span>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" class="input mt-1">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>
            <label class="block text-sm font-medium">
                Access level
                @if ($isSelf)
                    <input type="hidden" name="access_level" value="{{ $user->access_level }}">
                    <span class="mt-1 block text-sm font-normal">{{ \App\Models\AccessLevel::label($user->access_level) }} (you can't change your own)</span>
                @else
                    <select name="access_level" required class="input mt-1">
                        @foreach (\App\Models\AccessLevel::all() as $level)
                            <option value="{{ $level }}" @selected(old('access_level', $user->access_level) === $level)>{{ \App\Models\AccessLevel::label($level) }}</option>
                        @endforeach
                    </select>
                @endif
                @error('access_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>
            <button type="submit" class="btn">Save details</button>
        </form>

        {{-- Sites --}}
        <div class="rounded-lg border">
            <h2 class="border-b px-4 py-3 text-sm font-semibold">Sites</h2>
            @forelse ($user->sites as $site)
                <div class="flex items-center justify-between border-b px-4 py-3 text-sm last:border-b-0">
                    <a href="{{ route('admin.sites.show', $site->slug) }}" class="font-medium hover:underline">{{ $site->name }}</a>
                    {!! \App\Models\SiteRole::badge($site->pivot->role) !!}
                </div>
            @empty
                <p class="px-4 py-6 text-center text-sm text-secondary-foreground">Not a member of any site. Add them from a site's page.</p>
            @endforelse
        </div>

        {{-- Password --}}
        <form method="POST" action="{{ route('admin.users.password', $user) }}" class="flex flex-wrap items-end gap-3 rounded-lg border p-4" data-confirm="Set a new password for {{ $user->name }}?">
            @csrf
            <div class="w-full text-sm font-semibold">Reset password</div>
            <label class="block flex-1 text-sm font-medium">
                New password
                <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to generate one" class="input mt-1">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>
            <button type="submit" class="btn">Reset password</button>
        </form>

        {{-- Disable / delete --}}
        @unless ($isSelf)
            <div class="flex flex-wrap items-center gap-3 rounded-lg border border-red-200 p-4">
                <form method="POST" action="{{ route('admin.users.disabled', $user) }}" data-confirm="{{ $user->isDisabled() ? 'Enable' : 'Disable' }} {{ $user->name }}?">
                    @csrf
                    <button type="submit" class="btn-outline">
                        {{ $user->isDisabled() ? 'Enable user' : 'Disable user' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Permanently delete {{ $user->name }} and their API tokens? This can't be undone.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Delete user</button>
                </form>
            </div>
        @endunless
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin.users.show.js') }}"></script>
@endpush
