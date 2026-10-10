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
                            class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-900/10"
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
                            class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-900/10"
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
                            class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900/10"
                        >
                        <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900/10"
                    >
                        Sign in
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
