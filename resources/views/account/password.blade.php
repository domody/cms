@extends('layouts.admin', [
    'title' => 'Account',
    'pageTitle' => 'Account',
    'pageDescription' => auth()->user()->email,
    'breadcrumbs' => [
        ['label' => 'Account'],
    ],
])

@section('pageActions')
    <a href="{{ route('home') }}" class="text-sm text-gray-600 hover:text-gray-900">Back to the CMS</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('account.update') }}" class="max-w-md space-y-4 rounded-lg border p-4">
        @csrf
        <h2 class="text-sm font-semibold">Change password</h2>

        <label class="block text-sm font-medium">
            Current password
            <input type="password" name="current_password" required autocomplete="current-password" class="input mt-1">
            @error('current_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>
        <label class="block text-sm font-medium">
            New password
            <input type="password" name="password" required minlength="8" autocomplete="new-password" class="input mt-1">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </label>
        <label class="block text-sm font-medium">
            Confirm new password
            <input type="password" name="password_confirmation" required autocomplete="new-password" class="input mt-1">
        </label>

        <button type="submit" class="btn">Update password</button>
    </form>
@endsection
