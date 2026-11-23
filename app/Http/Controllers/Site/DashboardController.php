<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CollectionDefinition;
use App\Models\CollectionEntry;
use App\Models\Media;
use App\Models\Page;
use App\Models\Scopes\CurrentSite;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Overview of the current site's content. Members and recent activity
     * are only loaded for people who may open the Users and History pages.
     */
    public function index(Request $request): View
    {
        $site = app(CurrentSite::class)->get();
        $canManage = $request->user()->hasSiteAccess($site, 'siteManage');
        $collections = CollectionDefinition::orderBy('label')->get();

        return view('dashboard', [
            'site' => $site,
            'pageCounts' => Page::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'recentPages' => Page::latest('updated_at')->limit(6)->get(),
            'collections' => $collections,
            // Only declared collections: entries of a dropped collection are hidden everywhere else.
            'entryCounts' => CollectionEntry::whereIn('collection_key', $collections->pluck('key'))
                ->selectRaw('collection_key, count(*) as total')
                ->groupBy('collection_key')
                ->pluck('total', 'collection_key'),
            'mediaCount' => Media::count(),
            'storageUsed' => Media::storageUsed(),
            'storageLimit' => $site->storageLimit(),
            'memberCount' => $canManage ? $site->users()->count() : null,
            'activity' => $canManage ? ActivityLog::latest('id')->limit(8)->get() : null,
        ]);
    }
}
