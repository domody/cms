@extends('layouts.auth')

@section('content')
    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-semibold text-gray-900">{{ config('app.name', 'Laravel') }}</h1>
                <p class="mt-1 text-sm text-gray-500">Sign in to your account</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="input mt-1.5 placeholder:text-gray-400"
                            placeholder="you@example.com"
                        >
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <a href="#" class="text-sm text-gray-500 hover:text-gray-700">Forgot password?</a>
                        </div>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="input mt-1.5 placeholder:text-gray-400"
                            placeholder="••••••••"
                        >
                        @error('password')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center">
                        <input
                            id="remember"
                            type="checkbox"
                            name="remember"
                            class="checkbox"
                        >
                        <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                    </div>

                    <button
                        type="submit"
                        class="btn w-full"
                    >
                        Sign in
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
