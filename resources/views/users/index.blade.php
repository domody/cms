@extends('layouts.admin', [
    'title' => 'Users',
    'pageTitle' => 'Users',
    'pageDescription' => 'People who can sign in and manage this site.',
    'breadcrumbs' => [
        ['label' => 'Users'],
    ],
])

@section('pageActions')
    <x-button icon="add-01" :href="route('users.create')">Add user</x-button>
@endsection

@section('content')
    <x-table>
        <x-slot:head>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Added</th>
        </x-slot:head>

        @forelse ($members as $member)
            <tr data-href="{{ route('users.show', $member->id) }}" class="cursor-pointer hover:bg-muted">
                <td>
                    <a href="{{ route('users.show', $member->id) }}" class="font-medium">{{ $member->name }}</a>
                    @if ($member->is(auth()->user())) <span class="ms-1 text-xs text-secondary-foreground">(you)</span> @endif
                    @if ($member->isDisabled()) <x-badge tone="danger" class="ms-1">Disabled</x-badge> @endif
                </td>
                <td class="text-secondary-foreground">{{ $member->email }}</td>
                <td>{!! \App\Models\SiteRole::badge($member->pivot->role) !!}</td>
                <td class="text-secondary-foreground">{{ $member->pivot->created_at?->diffForHumans() ?? '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4">
                    <x-empty-state figure="keyboard" title="No users yet">
                        Get started and invite your first team member.
                        <x-slot:actions>
                            <x-button icon="add-01" :href="route('users.create')">Add user</x-button>
                        </x-slot:actions>
                    </x-empty-state>
                </td>
            </tr>
        @endforelse
    </x-table>
@endsection

@push('scripts')
    <script src="{{ asset('js/users.index.js') }}"></script>
@endpush
