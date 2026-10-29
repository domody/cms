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
    <a href="{{ route('admin.users.create') }}" class="btn">
        <x-hugeicons-add-01 aria-hidden="true" />
        Add user
    </a>
@endsection

@section('content')
    <div class="overflow-hidden rounded-lg border">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-muted text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Sites</th>
                    <th class="px-4 py-3">Roles</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($users as $user)
                    @php
                        $roles = $user->sites->map(fn ($site) => $site->pivot->role);
                        $distinctRoles = $roles->unique()->sortBy(fn ($role) => array_search($role, \App\Models\SiteRole::all()))->values();
                    @endphp
                    <tr data-href="{{ route('admin.users.show', $user) }}" class="cursor-pointer hover:bg-muted">
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('admin.users.show', $user) }}" class="hover:underline">{{ $user->name }}</a>
                            @if ($user->isDisabled()) <span class="ms-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Disabled</span> @endif
                        </td>
                        <td class="px-4 py-3 text-secondary-foreground">
                            @if ($user->sites->count() > 1)
                                {{ $user->sites->count() }} Sites
                            @else
                                {{ $user->sites->first()?->name ?? '—' }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($distinctRoles->isEmpty())
                                <span class="text-secondary-foreground">—</span>
                            @elseif ($distinctRoles->count() === 1)
                                {!! \App\Models\SiteRole::badge($distinctRoles->first()) !!}
                                @if ($roles->count() > 1)
                                    <span class="text-secondary-foreground">x{{ $roles->count() }}</span>
                                @endif
                            @else
                                {!! \App\Models\SiteRole::badge($distinctRoles->first()) !!}
                                <small class="text-secondary-foreground ml-1">+ {{ $distinctRoles->count() - 1 }} more</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-secondary-foreground">No users yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin.users.index.js') }}"></script>
@endpush
