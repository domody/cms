@extends('layouts.admin', [
    'title' => $site->name,
    'pageTitle' => $site->name,
    'pageDescription' => '/'.$site->slug,
    'breadcrumbs' => [
        ['label' => 'Admin'],
        ['label' => 'Sites', 'url' => route('admin.sites.index')],
        ['label' => $site->name],
    ],
])

@section('pageActions')
    <a href="{{ route('dashboard', $site->slug) }}" class="text-sm text-gray-600 hover:text-gray-900">Open site in the CMS</a>
@endsection

@section('content')
    <div class="max-w-3xl space-y-6 pb-8">
        @if (session('new_token'))
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm">
                <p class="font-semibold text-amber-900">Token "{{ session('new_token')['name'] }}" created</p>
                <p class="mt-1 text-amber-800">Copy it now: it is only shown once.</p>
                <input type="text" readonly value="{{ session('new_token')['plain'] }}" onfocus="this.select()" class="mt-2 block w-full rounded border border-amber-300 bg-white px-3 py-2 font-mono text-xs">
            </div>
        @endif

        {{-- Settings --}}
        <form method="POST" action="{{ route('admin.sites.update', $site->slug) }}" class="space-y-4 rounded-lg border p-4">
            @csrf
            <h2 class="text-sm font-semibold">Settings</h2>

            <label class="block text-sm font-medium">
                Name <span class="text-red-600">*</span>
                <input type="text" name="name" value="{{ old('name', $site->name) }}" required maxlength="255" class="input mt-1">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>

            <label class="block text-sm font-medium">
                Revalidate URL
                <input type="url" name="revalidate_url" value="{{ old('revalidate_url', $site->revalidate_url) }}" maxlength="2048" placeholder="https://client-site.com/api/revalidate" class="input mt-1">
                <span class="mt-1 block text-xs font-normal text-secondary-foreground">The frontend is pinged here when content is saved, so edits show up straight away. It needs both the URL and the secret; leave them empty to turn this off.</span>
                @error('revalidate_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>

            <label class="block text-sm font-medium">
                Revalidate secret
                <input type="password" name="revalidate_secret" value="" autocomplete="new-password" placeholder="{{ $site->revalidate_secret ? 'Set. Leave blank to keep it.' : 'Not set' }}" class="input mt-1">
                @error('revalidate_secret') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>

            <button type="submit" class="btn">Save settings</button>
        </form>

        {{-- Members --}}
        <div class="rounded-lg border">
            <h2 class="border-b px-4 py-3 text-sm font-semibold">Members</h2>
            <table class="min-w-full divide-y divide-border text-sm">
                <tbody class="divide-y divide-border">
                    @forelse ($site->users as $member)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.users.show', $member) }}" class="font-medium hover:underline">{{ $member->name }}</a>
                                <span class="ms-2 text-xs text-secondary-foreground">{{ $member->email }}</span>
                                @if ($member->isDisabled()) <span class="ms-2 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700">Disabled</span> @endif
                            </td>
                            <td class="px-4 py-3">{!! \App\Models\SiteRole::badge($member->pivot->role) !!}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.sites.members.destroy', [$site->slug, $member]) }}" data-confirm="Remove {{ $member->name }} from this site?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:underline">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-6 text-center text-secondary-foreground">No members yet.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if ($addableUsers->isNotEmpty())
                <form method="POST" action="{{ route('admin.sites.members.store', $site->slug) }}" class="flex flex-wrap items-end gap-3 border-t p-4">
                    @csrf
                    <label class="block text-sm font-medium">
                        Add a user
                        <select name="user_id" required class="input mt-1">
                            @foreach ($addableUsers as $candidate)
                                <option value="{{ $candidate->id }}">{{ $candidate->name }} ({{ $candidate->email }})</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium">
                        Role
                        <select name="role" required class="input mt-1">
                            @foreach (\App\Models\SiteRole::all() as $role)
                                <option value="{{ $role }}" @selected($role === \App\Models\SiteRole::EDITOR)>{{ \App\Models\SiteRole::label($role) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="btn">Add</button>
                </form>
            @endif
            <p class="border-t px-4 py-3 text-xs text-secondary-foreground">
                Roles are recorded but not enforced yet: every member can edit everything on the site.
                Need someone new? <a href="{{ route('admin.users.create') }}" class="underline">Create a user</a>.
            </p>
        </div>

        {{-- API tokens --}}
        <div class="rounded-lg border">
            <h2 class="border-b px-4 py-3 text-sm font-semibold">API tokens</h2>
            <table class="min-w-full divide-y divide-border text-sm">
                <tbody class="divide-y divide-border">
                    @forelse ($tokens as $token)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $token->name }}</td>
                            <td class="px-4 py-3 text-xs text-secondary-foreground">
                                {{ in_array('sync', $token->abilities ?? []) || in_array('*', $token->abilities ?? []) ? 'Read + sync' : 'Read only' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-secondary-foreground">
                                {{ $token->last_used_at ? 'Used '.$token->last_used_at->diffForHumans() : 'Never used' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.sites.tokens.destroy', [$site->slug, $token]) }}" data-confirm="Revoke &quot;{{ $token->name }}&quot;? Any frontend using it stops working.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:underline">Revoke</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-6 text-center text-secondary-foreground">No tokens yet.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <form method="POST" action="{{ route('admin.sites.tokens.store', $site->slug) }}" class="flex flex-wrap items-end gap-3 border-t p-4">
                @csrf
                <label class="block text-sm font-medium">
                    Name
                    <input type="text" name="name" required maxlength="255" placeholder="Production frontend" class="input mt-1">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block text-sm font-medium">
                    Type
                    <select name="type" required class="input mt-1">
                        <option value="read">Read only (production)</option>
                        <option value="sync">Read + sync (CI / dev)</option>
                    </select>
                </label>
                <button type="submit" class="btn">Create token</button>
            </form>
            <p class="border-t px-4 py-3 text-xs text-secondary-foreground">
                Tokens belong to the site, not to a person. They keep working until you revoke them or delete the site.
            </p>
        </div>

        {{-- Danger zone --}}
        <form method="POST" action="{{ route('admin.sites.destroy', $site->slug) }}" class="space-y-3 rounded-lg border border-red-200 p-4">
            @csrf
            @method('DELETE')
            <h2 class="text-sm font-semibold text-red-700">Delete site</h2>
            <p class="text-sm text-secondary-foreground">
                Permanently deletes this site with all its pages, collections, images and tokens.
                Type <span class="font-mono font-medium">{{ $site->slug }}</span> to confirm.
            </p>
            <input type="text" name="confirm_slug" autocomplete="off" class="input max-w-xs">
            @error('confirm_slug') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <button type="submit" class="btn-danger">Delete this site</button>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin.sites.show.js') }}"></script>
@endpush
