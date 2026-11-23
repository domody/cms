<?php

namespace App\View\Composers;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SiteController as AdminSiteController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Site\CollectionController;
use App\Http\Controllers\Site\DashboardController as SiteDashboardController;
use App\Http\Controllers\Site\HistoryController;
use App\Http\Controllers\Site\MediaController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\UserController as SiteUserController;
use App\Models\Scopes\CurrentSite;
use Illuminate\View\View;

class MenuComposer
{
    public function __construct(private CurrentSite $currentSite) {}

    public function compose(View $view): void
    {
        $user = auth()->user();
        $site = $this->currentSite->get();

        $view->with('currentSite', $site);
        $view->with('userSites', $user?->accessibleSites()->get() ?? collect());

        // Outside a site (/admin/...) there is no {site} to fill in, so the
        // site links can't be generated; show the admin menu instead.
        $items = $site === null ? $this->adminMenu() : $this->siteMenu();

        // Inside a site a customer's role on it narrows the menu too.
        $siteRole = $site !== null && $user !== null && ! $user->isAdmin() ? $user->roleOn($site) : null;

        $view->with('sidebarMenu', array_values(array_filter(
            $items,
            fn (array $item) => match (true) {
                $user === null => false,
                $site !== null && ! $user->isAdmin() => $siteRole !== null && $user->canAccessAs($siteRole, ...$item['action']),
                default => $user->canAccess(...$item['action']),
            },
        )));
    }

    /**
     * Links below omit {site}; SetCurrentSite fills it in via URL::defaults().
     *
     * @return array<int, array<string, mixed>>
     */
    private function siteMenu(): array
    {
        return [
            [
                'type' => 'link',
                'text' => 'Dashboard',
                'icon' => 'hugeicons-home-04',
                'url' => route('dashboard'),
                'route' => 'dashboard',
                'action' => [SiteDashboardController::class, 'index'],
            ],
            [
                'type' => 'link',
                'text' => 'Pages',
                'icon' => 'hugeicons-file-01',
                'url' => route('pages.index'),
                'route' => 'pages.*',
                'action' => [PageController::class, 'index'],
            ],
            [
                'type' => 'link',
                'text' => 'Collections',
                'icon' => 'hugeicons-folder-01',
                'url' => route('collections.index'),
                'route' => 'collections.*',
                'action' => [CollectionController::class, 'index'],
            ],
            [
                'type' => 'link',
                'text' => 'Media',
                'icon' => 'hugeicons-image-01',
                'url' => route('media.index'),
                'route' => 'media.*',
                'action' => [MediaController::class, 'index'],
            ],
            [
                'type' => 'link',
                'text' => 'Users',
                'icon' => 'hugeicons-user-group',
                'url' => route('users.index'),
                'route' => 'users.*',
                'action' => [SiteUserController::class, 'index'],
            ],
            [
                'type' => 'link',
                'text' => 'History',
                'icon' => 'hugeicons-clock-01',
                'url' => route('history.index'),
                'route' => 'history.*',
                'action' => [HistoryController::class, 'index'],
            ],
            [
                'type' => 'link',
                'text' => 'Admin',
                'icon' => 'hugeicons-dashboard-square-01',
                'url' => route('admin.dashboard'),
                'route' => 'admin.*',
                'action' => [AdminDashboardController::class, 'index'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function adminMenu(): array
    {
        return [
            [
                'type' => 'link',
                'text' => 'Dashboard',
                'icon' => 'hugeicons-home-04',
                'url' => route('admin.dashboard'),
                'route' => 'admin.dashboard',
                'action' => [AdminDashboardController::class, 'index'],
            ],
            [
                'type' => 'link',
                'text' => 'Sites',
                'icon' => 'hugeicons-globe-02',
                'url' => route('admin.sites.index'),
                'route' => 'admin.sites.*',
                'action' => [AdminSiteController::class, 'index'],
            ],
            [
                'type' => 'link',
                'text' => 'Users',
                'icon' => 'hugeicons-user-group',
                'url' => route('admin.users.index'),
                'route' => 'admin.users.*',
                'action' => [AdminUserController::class, 'index'],
            ],
        ];
    }
}
