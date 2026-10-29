@extends('layouts.admin', [
    'title' => $member->name,
    'pageTitle' => $member->name,
    'pageDescription' => $member->email,
    'breadcrumbs' => [
        ['label' => 'Users', 'url' => route('users.index')],
        ['label' => $member->name],
    ],
])

@php
    $isSelf = $member->is(auth()->user());
@endphp

@section('pageActions')
    <a href="{{ route('history.index', ['type' => 'user', 'subject' => $member->id]) }}" class="text-sm text-gray-600 hover:text-gray-900">History</a>
@endsection

@section('content')
    <div class="max-w-3xl space-y-6 pb-8">
        @if (session('shown_password'))
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm">
                <p class="font-semibold text-amber-900">Password for {{ $member->email }}</p>
                <p class="mt-1 text-amber-800">Copy it now and pass it on: it is only shown once.</p>
                <input type="text" readonly value="{{ session('shown_password') }}" onfocus="this.select()" class="mt-2 block w-full rounded border border-amber-300 bg-white px-3 py-2 font-mono text-xs">
            </div>
        @endif

        @if ($member->isDisabled())
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">This account is disabled, so they can't sign in.</div>
        @endif

        {{-- Role --}}
        <form method="POST" action="{{ route('users.role', $member->id) }}" class="flex flex-wrap items-end gap-3 rounded-lg border p-4">
            @csrf
            <div class="w-full text-sm font-semibold">Role on this site</div>
            @if ($isSelf || ! $canChangeRole)
                <p class="text-sm">{!! \App\Models\SiteRole::badge($role) !!} <span class="ms-2 text-secondary-foreground">{{ $isSelf ? "You can't change your own role." : 'Only owners can change roles.' }}</span></p>
            @else
                <label class="block flex-1 text-sm font-medium">
                    Role
                    <select name="role" class="input mt-1">
                        @foreach (\App\Models\SiteRole::all() as $option)
                            <option value="{{ $option }}" @selected($role === $option)>{{ \App\Models\SiteRole::label($option) }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <button type="submit" class="btn">Change role</button>
            @endif
        </form>

        @if ($canEditAccount)
            {{-- Details --}}
            <form method="POST" action="{{ route('users.update', $member->id) }}" class="space-y-4 rounded-lg border p-4">
                @csrf
                <h2 class="text-sm font-semibold">Details</h2>

                <label class="block text-sm font-medium">
                    Name <span class="text-red-600">*</span>
                    <input type="text" name="name" value="{{ old('name', $member->name) }}" required maxlength="255" class="input mt-1">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block text-sm font-medium">
                    Email <span class="text-red-600">*</span>
                    <input type="email" name="email" value="{{ old('email', $member->email) }}" required maxlength="255" class="input mt-1">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <button type="submit" class="btn">Save details</button>
            </form>

            {{-- Password --}}
            <form method="POST" action="{{ route('users.password', $member->id) }}" class="flex flex-wrap items-end gap-3 rounded-lg border p-4" data-confirm="Set a new password for {{ $member->name }}?">
                @csrf
                <div class="w-full text-sm font-semibold">Reset password</div>
                <label class="block flex-1 text-sm font-medium">
                    New password
                    <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to generate one" class="input mt-1">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <button type="submit" class="btn">Reset password</button>
            </form>
        @else
            <p class="rounded-lg border p-4 text-sm text-secondary-foreground">
                This person's name, email and password can't be changed here because their account is shared with another site.
            </p>
        @endif

        {{-- Remove --}}
        @if (! $isSelf && $canRemove)
            <div class="rounded-lg border border-red-200 p-4">
                <form method="POST" action="{{ route('users.destroy', $member->id) }}" data-confirm="Remove {{ $member->name }} from this site?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Remove from site</button>
                </form>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/users.show.js') }}"></script>
@endpush
