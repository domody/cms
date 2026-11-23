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
    <x-button variant="subtle" icon="clock-01" :href="route('history.index', ['type' => 'user', 'subject' => $member->id])">History</x-button>
@endsection

@section('content')
    <div class="max-w-3xl space-y-4">
        @if (session('shown_password'))
            <x-one-time-secret :title="'Password for '.$member->email" :value="session('shown_password')" />
        @endif

        @if ($member->isDisabled())
            <x-callout tone="danger">This account is disabled, so they can't sign in.</x-callout>
        @endif

        <x-card title="Role on this site">
            @if ($isSelf || ! $canChangeRole)
                <p class="flex items-center gap-2 text-sm">
                    {!! \App\Models\SiteRole::badge($role) !!}
                    <span class="text-secondary-foreground">{{ $isSelf ? "You can't change your own role." : 'Only owners can change roles.' }}</span>
                </p>
            @else
                <form method="POST" action="{{ route('users.role', $member->id) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <x-field label="Role" name="role" class="min-w-48 flex-1">
                        <select name="role" class="input">
                            @foreach (\App\Models\SiteRole::all() as $option)
                                <option value="{{ $option }}" @selected($role === $option)>{{ \App\Models\SiteRole::label($option) }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-button type="submit" class="h-[2.375rem]">Change role</x-button>
                </form>
            @endif
        </x-card>

        @if ($canEditAccount)
            <x-card title="Details">
                <form method="POST" action="{{ route('users.update', $member->id) }}" class="space-y-5">
                    @csrf
                    <div class="grid gap-x-4 gap-y-5 sm:grid-cols-2">
                        <x-field label="Name" name="name" required>
                            <input type="text" name="name" value="{{ old('name', $member->name) }}" required maxlength="255" class="input">
                        </x-field>
                        <x-field label="Email" name="email" required>
                            <input type="email" name="email" value="{{ old('email', $member->email) }}" required maxlength="255" class="input">
                        </x-field>
                    </div>
                    <x-button type="submit">Save details</x-button>
                </form>
            </x-card>

            <x-card title="Reset password">
                <form method="POST" action="{{ route('users.password', $member->id) }}" class="flex flex-wrap items-end gap-3" data-confirm="Set a new password for {{ $member->name }}?">
                    @csrf
                    <x-field label="New password" name="password" class="min-w-48 flex-1">
                        <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to generate one" class="input">
                    </x-field>
                    <x-button type="submit" variant="outline" class="h-[2.375rem]">Reset password</x-button>
                </form>
            </x-card>
        @else
            <x-callout>This person's name, email and password can't be changed here because their account is shared with another site.</x-callout>
        @endif

        @if (! $isSelf && $canRemove)
            <x-card title="Remove from site" description="They lose access to this site. Their account stays." tone="danger">
                <form method="POST" action="{{ route('users.destroy', $member->id) }}" data-confirm="Remove {{ $member->name }} from this site?">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger-outline">Remove from site</x-button>
                </form>
            </x-card>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/users.show.js') }}"></script>
@endpush
