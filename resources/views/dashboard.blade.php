@extends('layouts.admin', [
    'title' => 'Dashboard',
    'pageTitle' => $site->name,
    'pageDescription' => 'Overview of your content.',
    'breadcrumbs' => [
        ['label' => 'Home'],
        ['label' => 'Dashboard'],
    ],
])

@use('App\Models\PageStatus')

@php
    $published = $pageCounts[PageStatus::PUBLISHED] ?? 0;
    $drafts = $pageCounts[PageStatus::DRAFT] ?? 0;
    $entryTotal = $entryCounts->sum();
@endphp

@section('content')
    <div class="space-y-4">
        <div @class(['grid gap-3 sm:grid-cols-2', 'lg:grid-cols-4' => $memberCount !== null, 'lg:grid-cols-3' => $memberCount === null])>
            <x-stat-tile label="Pages" icon="file-01" :value="$pageCounts->sum()" :href="route('pages.index')">
                {{ $published }} published · {{ $drafts }} {{ $drafts === 1 ? 'draft' : 'drafts' }}
            </x-stat-tile>

            <x-stat-tile label="Collection entries" icon="folder-01" :value="$entryTotal" :href="route('collections.index')">
                {{ trans_choice('{0} No collections|{1} In 1 collection|[2,*] Across :count collections', $collections->count()) }}
            </x-stat-tile>

            <x-stat-tile label="Images" icon="image-01" :value="$mediaCount" :href="route('media.index')">
                <x-usage-meter :used="$storageUsed" :limit="$storageLimit" />
            </x-stat-tile>

            @if ($memberCount !== null)
                <x-stat-tile label="Members" icon="user-group" :value="$memberCount" :href="route('users.index')">
                    {{ \App\Models\SitePlan::label($site->plan) }} plan
                </x-stat-tile>
            @endif
        </div>

        <div class="grid items-start gap-4 lg:grid-cols-3">
            <x-card title="Recently edited" description="Pages changed most recently." :flush="true" class="lg:col-span-2">
                @if ($recentPages->isNotEmpty())
                    <x-slot:actions>
                        <x-button variant="ghost" :href="route('pages.index')">All pages</x-button>
                    </x-slot:actions>
                @endif

                <table class="data-table">
                    <tbody>
                        @forelse ($recentPages as $page)
                            <tr data-href="{{ route('pages.edit', $page) }}" class="cursor-pointer hover:bg-muted">
                                <td class="min-w-0">
                                    <a href="{{ route('pages.edit', $page) }}" class="font-medium">{{ $page->title }}</a>
                                    <span class="ms-2 font-mono text-xs text-muted-foreground">/{{ $page->slug }}</span>
                                </td>
                                <td class="w-24">{!! PageStatus::badge($page->status) !!}</td>
                                <td class="w-32 whitespace-nowrap text-right text-secondary-foreground" title="{{ $page->updated_at->toDateTimeString() }}">
                                    {{ $page->updated_at->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr><td>
                                <x-empty-state figure="stack" title="No pages yet">
                                    Pages appear once your frontend declares them.
                                </x-empty-state>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-card>

            <x-card title="Collections">
                @if ($collections->isNotEmpty())
                    <x-slot:actions>
                        <x-button variant="ghost" :href="route('collections.index')">All</x-button>
                    </x-slot:actions>
                @endif

                <div class="space-y-2">
                    @forelse ($collections as $collection)
                        <x-link-tile :href="route('collections.show', ['collection' => $collection->key])" icon="folder-01" :title="$collection->label">
                            {{ trans_choice('{0} No entries|{1} 1 entry|[2,*] :count entries', $entryCounts[$collection->key] ?? 0) }}
                        </x-link-tile>
                    @empty
                        <x-empty-state figure="cabinet" title="No collections yet" class="py-6">
                            Collections appear once your frontend declares them.
                        </x-empty-state>
                    @endforelse
                </div>
            </x-card>
        </div>

        @if ($activity !== null)
            <x-card title="Recent activity" description="The latest changes on this site." :flush="true">
                @if ($activity->isNotEmpty())
                    <x-slot:actions>
                        <x-button variant="ghost" :href="route('history.index')">Full history</x-button>
                    </x-slot:actions>
                @endif

                <table class="data-table">
                    <tbody>
                        @forelse ($activity as $log)
                            <tr>
                                <td><x-activity-summary :log="$log" /></td>
                                <td class="whitespace-nowrap text-secondary-foreground">{{ $log->actor() }}</td>
                                <td class="w-32 whitespace-nowrap text-right text-secondary-foreground" title="{{ $log->created_at->toDateTimeString() }}">
                                    {{ $log->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr><td>
                                <x-empty-state figure="rail" title="No activity yet">
                                    Changes made on this site will show up here.
                                </x-empty-state>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-card>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
