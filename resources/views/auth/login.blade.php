@extends('layouts.auth')

@section('body_class', 'bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center justify-center min-h-screen')

@section('content')
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <h1 class="text-xl font-medium dark:text-[#EDEDEC]">Sign in</h1>
            <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">Enter your email and password to access your account.</p>
        </div>

        <div class="rounded-lg bg-white dark:bg-[#161615] p-6 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
            <form method="POST" action="#" class="flex flex-col gap-5">
                @csrf

                <div class="flex flex-col gap-2">
                    <label for="email" class="text-sm font-medium dark:text-[#EDEDEC]">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        autofocus
                        placeholder="you@example.com"
                        class="w-full rounded-sm border border-[#19140035] dark:border-[#3E3E3A] bg-transparent px-3 py-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC] placeholder:text-[#A1A09A] focus:outline-none focus:border-[#f53003] dark:focus:border-[#FF4433]"
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <label for="password" class="text-sm font-medium dark:text-[#EDEDEC]">Password</label>
                        <a href="#" class="text-sm font-medium underline underline-offset-4 text-[#f53003] dark:text-[#FF4433]">
                            Forgot password?
                        </a>
                    </div>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        placeholder="••••••••"
                        class="w-full rounded-sm border border-[#19140035] dark:border-[#3E3E3A] bg-transparent px-3 py-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC] placeholder:text-[#A1A09A] focus:outline-none focus:border-[#f53003] dark:focus:border-[#FF4433]"
                    />
                </div>

                <label class="flex items-center gap-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        class="rounded-sm border-[#19140035] dark:border-[#3E3E3A] text-[#f53003] focus:ring-[#f53003]"
                    />
                    Remember me
                </label>

                <button
                    type="submit"
                    class="mt-1 inline-block w-full rounded-sm border border-black bg-[#1b1b18] px-5 py-2 text-center text-sm leading-normal text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
                >
                    Sign in
                </button>
            </form>
        </div>
    </div>
@endsection
