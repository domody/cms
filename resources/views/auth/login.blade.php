@extends('layouts.auth')

@section('content')
    <div class="flex min-h-screen items-center justify-center bg-muted px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-semibold tracking-tight">{{ config('app.name', 'Laravel') }}</h1>
                <p class="mt-1 text-sm text-secondary-foreground">Sign in to your account</p>
            </div>

            <x-card class="p-3 shadow-sm">
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <x-field label="Email" name="email">
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@example.com" class="input">
                    </x-field>

                    <x-field label="Password" name="password">
                        <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••" class="input">
                    </x-field>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="remember" class="checkbox">
                            Remember me
                        </label>
                        <a href="#" class="text-sm text-secondary-foreground hover:text-foreground">Forgot password?</a>
                    </div>

                    <x-button type="submit" class="w-full">Sign in</x-button>
                </form>
            </x-card>
        </div>
    </div>
@endsection
