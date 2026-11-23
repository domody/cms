@props(['user', 'initials'])

<span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-info/15 text-xs font-semibold text-primary-foreground">
    {{ $initials }}
</span>
<span class="flex min-w-0 flex-1 flex-col">
    <span class="truncate text-sm font-medium">{{ $user->name }}</span>
    <span class="truncate text-xs text-secondary-foreground">{{ $user->email }}</span>
</span>
