@extends('layouts.admin', [
    'title' => 'Account',
    'pageTitle' => 'Account',
    'pageDescription' => auth()->user()->email,
    'breadcrumbs' => [
        ['label' => 'Account'],
    ],
])

@section('pageActions')
    <x-button variant="subtle" icon="arrow-left-01" :href="route('home')">Back to the CMS</x-button>
@endsection

@section('content')
    <x-card title="Change password" class="max-w-md">
        <form method="POST" action="{{ route('account.update') }}" class="space-y-5">
            @csrf

            <x-field label="Current password" name="current_password">
                <input type="password" name="current_password" required autocomplete="current-password" class="input">
            </x-field>
            <x-field label="New password" name="password">
                <input type="password" name="password" required minlength="8" autocomplete="new-password" class="input">
            </x-field>
            <x-field label="Confirm new password" name="password_confirmation">
                <input type="password" name="password_confirmation" required autocomplete="new-password" class="input">
            </x-field>

            <x-button type="submit">Update password</x-button>
        </form>
    </x-card>
@endsection
