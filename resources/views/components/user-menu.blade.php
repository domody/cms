@props(['user' => auth()->user()])

@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<div {{ $attributes->class(['relative']) }} data-user-menu>
    <button
        type="button"
        data-user-menu-toggle
        aria-haspopup="true"
        aria-expanded="false"
        class="flex w-full items-center gap-3 rounded px-3 py-2 text-left hover:bg-background/80"
    >
        <x-user-menu-identity :initials="$initials" :user="$user" />
        <x-hugeicons-unfold-more class="size-4 shrink-0 text-secondary-foreground" aria-hidden="true" />
    </button>
    <div
        data-user-menu-panel
        class="fixed z-50 hidden w-60 rounded-lg border bg-background p-1 shadow-lg"
    >
        <div class="flex items-center gap-3 px-3 py-2">
            <x-user-menu-identity :initials="$initials" :user="$user" />
        </div>
        <div class="my-1 border-t"></div>
        <a href="{{ route('account.edit') }}" class="flex items-center gap-2 rounded px-3 py-2 text-sm hover:bg-muted">
            <x-hugeicons-user-02 class="size-4 shrink-0" aria-hidden="true" />
            Account
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm hover:bg-muted">
                <x-hugeicons-logout-01 class="size-4 shrink-0" aria-hidden="true" />
                Log out
            </button>
        </form>
    </div>
</div>

@once
    @push('scripts')
        <script src="{{ asset('js/components.user-menu.js') }}"></script>
    @endpush
@endonce
