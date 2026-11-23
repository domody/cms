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
    <x-button variant="subtle" icon="square-arrow-up-right" :href="route('dashboard', $site->slug)">Open site in the CMS</x-button>
@endsection

@section('content')
    <div class="max-w-3xl space-y-4">
        @if (session('new_token'))
            <x-one-time-secret :title="'Token “'.session('new_token')['name'].'” created'" :value="session('new_token')['plain']" />
        @endif

        <x-card title="Settings">
            <form method="POST" action="{{ route('admin.sites.update', $site->slug) }}" class="space-y-5">
                @csrf

                <x-field label="Name" name="name" required>
                    <input type="text" name="name" value="{{ old('name', $site->name) }}" required maxlength="255" class="input">
                </x-field>

                <x-field label="Revalidate URL" name="revalidate_url" hint="The frontend is pinged here when content is saved, so edits show up straight away. It needs both the URL and the secret; leave them empty to turn this off.">
                    <input type="url" name="revalidate_url" value="{{ old('revalidate_url', $site->revalidate_url) }}" maxlength="2048" placeholder="https://client-site.com/api/revalidate" class="input">
                </x-field>

                <x-field label="Revalidate secret" name="revalidate_secret">
                    <input type="password" name="revalidate_secret" value="" autocomplete="new-password" placeholder="{{ $site->revalidate_secret ? 'Set. Leave blank to keep it.' : 'Not set' }}" class="input">
                </x-field>

                <x-button type="submit">Save settings</x-button>
            </form>
        </x-card>

        <x-card title="Members" :flush="true">
            <x-slot:footer>
                Roles are recorded but not enforced yet: every member can edit everything on the site.
                Need someone new? <a href="{{ route('admin.users.create') }}" class="underline underline-offset-2">Create a user</a>.
            </x-slot:footer>

            <table class="data-table">
                <tbody>
                    @forelse ($site->users as $member)
                        <tr>
                            <td>
                                <a href="{{ route('admin.users.show', $member) }}" class="font-medium hover:underline">{{ $member->name }}</a>
                                <span class="ms-2 text-xs text-secondary-foreground">{{ $member->email }}</span>
                                @if ($member->isDisabled()) <x-badge tone="danger" class="ms-1">Disabled</x-badge> @endif
                            </td>
                            <td>{!! \App\Models\SiteRole::badge($member->pivot->role) !!}</td>
                            <td class="w-10 text-right">
                                <form method="POST" action="{{ route('admin.sites.members.destroy', [$site->slug, $member]) }}" data-confirm="Remove {{ $member->name }} from this site?">
                                    @csrf
                                    @method('DELETE')
                                    <x-icon-button type="submit" icon="delete-02" :label="'Remove '.$member->name" tone="danger" />
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td>
                            <x-empty-state figure="lockers" title="No members yet">
                                Give your client's team access to this site.
                            </x-empty-state>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>

            @if ($addableUsers->isNotEmpty())
                <form method="POST" action="{{ route('admin.sites.members.store', $site->slug) }}" class="flex flex-wrap items-end gap-3 border-t bg-muted/50 px-5 py-4">
                    @csrf
                    <x-field label="Add a user" class="min-w-48 flex-1">
                        <select name="user_id" required class="input bg-background">
                            @foreach ($addableUsers as $candidate)
                                <option value="{{ $candidate->id }}">{{ $candidate->name }} ({{ $candidate->email }})</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field label="Role">
                        <select name="role" required class="input bg-background">
                            @foreach (\App\Models\SiteRole::all() as $role)
                                <option value="{{ $role }}" @selected($role === \App\Models\SiteRole::EDITOR)>{{ \App\Models\SiteRole::label($role) }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-button type="submit" variant="outline" class="h-[2.375rem]">Add</x-button>
                </form>
            @endif
        </x-card>

        <x-card title="API tokens" :flush="true">
            <x-slot:footer>Tokens belong to the site, not to a person. They keep working until you revoke them or delete the site.</x-slot:footer>

            <table class="data-table">
                <tbody>
                    @forelse ($tokens as $token)
                        <tr>
                            <td class="font-medium">{{ $token->name }}</td>
                            <td>
                                <x-badge>{{ in_array('sync', $token->abilities ?? []) || in_array('*', $token->abilities ?? []) ? 'Read + sync' : 'Read only' }}</x-badge>
                            </td>
                            <td class="text-xs text-secondary-foreground">
                                {{ $token->last_used_at ? 'Used '.$token->last_used_at->diffForHumans() : 'Never used' }}
                            </td>
                            <td class="w-10 text-right">
                                <form method="POST" action="{{ route('admin.sites.tokens.destroy', [$site->slug, $token]) }}" data-confirm="Revoke &quot;{{ $token->name }}&quot;? Any frontend using it stops working.">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="danger-ghost">Revoke</x-button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td>
                            <x-empty-state figure="padlock" title="No tokens yet">
                                Create a token to let your frontend read this site's content.
                            </x-empty-state>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>

            <form method="POST" action="{{ route('admin.sites.tokens.store', $site->slug) }}" class="flex flex-wrap items-end gap-3 border-t bg-muted/50 px-5 py-4">
                @csrf
                <x-field label="Name" name="name" class="min-w-48 flex-1">
                    <input type="text" name="name" required maxlength="255" placeholder="Production frontend" class="input bg-background">
                </x-field>
                <x-field label="Type">
                    <select name="type" required class="input bg-background">
                        <option value="read">Read only (production)</option>
                        <option value="sync">Read + sync (CI / dev)</option>
                    </select>
                </x-field>
                <x-button type="submit" variant="outline" icon="key-01" class="h-[2.375rem]">Create token</x-button>
            </form>
        </x-card>

        <x-card title="Delete site" tone="danger">
            <form method="POST" action="{{ route('admin.sites.destroy', $site->slug) }}" class="space-y-3">
                @csrf
                @method('DELETE')
                <p class="text-sm text-secondary-foreground">
                    Permanently deletes this site with all its pages, collections, images and tokens.
                    Type <span class="font-mono font-medium text-foreground">{{ $site->slug }}</span> to confirm.
                </p>
                <x-field name="confirm_slug" class="max-w-xs">
                    <input type="text" name="confirm_slug" autocomplete="off" aria-label="Site slug to confirm" class="input">
                </x-field>
                <x-button type="submit" variant="danger">Delete this site</x-button>
            </form>
        </x-card>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin.sites.show.js') }}"></script>
@endpush
