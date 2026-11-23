@extends('layouts.admin', [
    'title' => 'Users',
    'pageTitle' => 'Users',
    'pageDescription' => 'Every user in the CMS.',
    'breadcrumbs' => [
        ['label' => 'Admin'],
        ['label' => 'Users'],
    ],
])

@section('pageActions')
    <x-button icon="add-01" :href="route('admin.users.create')">Add user</x-button>
@endsection

@section('content')
    <x-table>
        <x-slot:head>
            <th>User</th>
            <th>Sites</th>
            <th>Roles</th>
        </x-slot:head>

        @forelse ($users as $user)
            @php
                $roles = $user->sites->map(fn ($site) => $site->pivot->role);
                $distinctRoles = $roles->unique()->sortBy(fn ($role) => array_search($role, \App\Models\SiteRole::all()))->values();
            @endphp
            <tr data-href="{{ route('admin.users.show', $user) }}" class="cursor-pointer hover:bg-muted">
                <td>
                    <a href="{{ route('admin.users.show', $user) }}" class="font-medium">{{ $user->name }}</a>
                    @if ($user->isDisabled()) <x-badge tone="danger" class="ms-1">Disabled</x-badge> @endif
                </td>
                <td class="text-secondary-foreground">
                    @if ($user->sites->count() > 1)
                        {{ $user->sites->count() }} Sites
                    @else
                        {{ $user->sites->first()?->name ?? '—' }}
                    @endif
                </td>
                <td>
                    @if ($distinctRoles->isEmpty())
                        <span class="text-secondary-foreground">—</span>
                    @elseif ($distinctRoles->count() === 1)
                        {!! \App\Models\SiteRole::badge($distinctRoles->first()) !!}
                        @if ($roles->count() > 1)
                            <span class="text-xs text-secondary-foreground">x{{ $roles->count() }}</span>
                        @endif
                    @else
                        {!! \App\Models\SiteRole::badge($distinctRoles->first()) !!}
                        <span class="ms-1 text-xs text-secondary-foreground">+ {{ $distinctRoles->count() - 1 }} more</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3">
                    <x-empty-state figure="keyboard" title="No users yet">
                        Get started and create the first account.
                        <x-slot:actions>
                            <x-button icon="add-01" :href="route('admin.users.create')">Add user</x-button>
                        </x-slot:actions>
                    </x-empty-state>
                </td>
            </tr>
        @endforelse
    </x-table>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin.users.index.js') }}"></script>
@endpush
