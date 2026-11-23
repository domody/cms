<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Media;
use App\Models\Page;
use App\Models\PersonalAccessToken;
use App\Models\Scopes\SiteScope;
use App\Models\Site;
use App\Models\User;
use Illuminate\View\View;

/**
 * Whole-CMS area (/admin), outside any single site. There is no current site
 * here, so per-site figures are read across sites on purpose, one grouped
 * query per table.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $sites = Site::withCount('users')->orderBy('name')->get();

        return view('admin.dashboard', [
            'sites' => $sites,
            'sitesById' => $sites->keyBy('id'),
            'userCount' => User::count(),
            'pageCounts' => Page::withoutGlobalScope(SiteScope::class)
                ->selectRaw('site_id, count(*) as total')->groupBy('site_id')->pluck('total', 'site_id'),
            'storageUsed' => Media::withoutGlobalScope(SiteScope::class)
                ->selectRaw('site_id, sum(stored_bytes) as total')->groupBy('site_id')->pluck('total', 'site_id'),
            'lastApiUse' => PersonalAccessToken::whereNotNull('site_id')
                ->selectRaw('site_id, max(last_used_at) as last_used_at')->groupBy('site_id')->pluck('last_used_at', 'site_id'),
            'activity' => ActivityLog::withoutGlobalScope(SiteScope::class)->latest('id')->limit(8)->get(),
        ]);
    }
}
