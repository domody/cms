@extends('layouts.admin', [
    'title' => 'Admin dashboard',
    'pageTitle' => 'Admin dashboard',
    'pageDescription' => 'Overview of every site.',
    'breadcrumbs' => [
        ['label' => 'Admin'],
        ['label' => 'Dashboard'],
    ],
])

@section('pageActions')
    <x-button icon="add-01" :href="route('admin.sites.create')">Add site</x-button>
@endsection

@section('content')
    <div class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <x-stat-tile label="Sites" icon="globe-02" :value="$sites->count()" :href="route('admin.sites.index')" />
            <x-stat-tile label="Users" icon="user-group" :value="$userCount" :href="route('admin.users.index')" />
            <x-stat-tile label="Images stored" icon="image-01" :value="\App\Models\Media::formatBytes((int) $storageUsed->sum())">
                Across every site
            </x-stat-tile>
        </div>

        <x-card title="Sites" description="Open a site to edit its content, or its settings to manage members and tokens." :flush="true">
            <table class="data-table">
                @if ($sites->isNotEmpty())
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th>Plan</th>
                            <th class="text-right">Pages</th>
                            <th class="text-right">Members</th>
                            <th class="w-48">Storage</th>
                            <th>API last used</th>
                            <th><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                @endif
                <tbody>
                    @forelse ($sites as $site)
                        <tr data-href="{{ route('dashboard', $site->slug) }}" class="cursor-pointer hover:bg-muted">
                            <td>
                                <a href="{{ route('dashboard', $site->slug) }}" class="font-medium">{{ $site->name }}</a>
                                <span class="ms-2 font-mono text-xs text-muted-foreground">/{{ $site->slug }}</span>
                            </td>
                            <td>{!! \App\Models\SitePlan::badge($site->plan) !!}</td>
                            <td class="text-right tabular-nums text-secondary-foreground">{{ $pageCounts[$site->id] ?? 0 }}</td>
                            <td class="text-right tabular-nums text-secondary-foreground">{{ $site->users_count }}</td>
                            <td><x-usage-meter :used="(int) ($storageUsed[$site->id] ?? 0)" :limit="$site->storageLimit()" /></td>
                            <td class="whitespace-nowrap text-secondary-foreground">
                                @if ($lastUsed = $lastApiUse[$site->id] ?? null)
                                    <span title="{{ $lastUsed }}">{{ \Illuminate\Support\Carbon::parse($lastUsed)->diffForHumans() }}</span>
                                @else
                                    Never
                                @endif
                            </td>
                            <td class="w-10 text-right">
                                <x-icon-button icon="settings-02" :label="'Settings for '.$site->name" :href="route('admin.sites.show', $site->slug)" />
                            </td>
                        </tr>
                    @empty
                        <tr><td>
                            <x-empty-state figure="hub" title="No sites yet">
                                Get started and add your first client site.
                                <x-slot:actions>
                                    <x-button icon="add-01" :href="route('admin.sites.create')">Add site</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        <x-card title="Recent activity" description="The latest changes across every site." :flush="true">
            <table class="data-table">
                <tbody>
                    @forelse ($activity as $log)
                        <tr>
                            <td class="whitespace-nowrap">
                                @if ($logSite = $sitesById[$log->site_id] ?? null)
                                    <a href="{{ route('history.index', $logSite->slug) }}" class="font-medium hover:underline">{{ $logSite->name }}</a>
                                @endif
                            </td>
                            <td><x-activity-summary :log="$log" /></td>
                            <td class="whitespace-nowrap text-secondary-foreground">{{ $log->actor() }}</td>
                            <td class="w-32 whitespace-nowrap text-right text-secondary-foreground" title="{{ $log->created_at->toDateTimeString() }}">
                                {{ $log->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr><td>
                            <x-empty-state figure="rail" title="No activity yet">
                                Changes made on any site will show up here.
                            </x-empty-state>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin.dashboard.js') }}"></script>
@endpush
