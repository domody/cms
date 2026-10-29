@extends('layouts.admin', [
    'title' => 'Users',
    'pageTitle' => 'Users',
    'pageDescription' => 'People who can sign in and manage this site.',
    'breadcrumbs' => [
        ['label' => 'Users'],
    ],
])

@section('pageActions')
    <a href="{{ route('users.create') }}" class="btn">
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
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Added</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($members as $member)
                    <tr data-href="{{ route('users.show', $member->id) }}" class="cursor-pointer hover:bg-muted">
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('users.show', $member->id) }}" class="hover:underline">{{ $member->name }}</a>
                            @if ($member->is(auth()->user())) <span class="ms-2 text-xs font-normal text-secondary-foreground">(you)</span> @endif
                            @if ($member->isDisabled()) <span class="ms-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Disabled</span> @endif
                        </td>
                        <td class="px-4 py-3 text-secondary-foreground">{{ $member->email }}</td>
                        <td class="px-4 py-3">{!! \App\Models\SiteRole::badge($member->pivot->role) !!}</td>
                        <td class="px-4 py-3 text-secondary-foreground">{{ $member->pivot->created_at?->diffForHumans() ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-secondary-foreground">No users yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/users.index.js') }}"></script>
@endpush
