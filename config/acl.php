<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SiteController as AdminSiteController;
use App\Http\Controllers\Admin\SiteMemberController as AdminSiteMemberController;
use App\Http\Controllers\Admin\SiteTokenController as AdminSiteTokenController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Site\CollectionController;
use App\Http\Controllers\Site\DashboardController as SiteDashboardController;
use App\Http\Controllers\Site\HistoryController;
use App\Http\Controllers\Site\MediaController;
use App\Http\Controllers\Site\MediaFolderController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\UserController as SiteUserController;
use App\Models\AccessLevel;
use App\Models\SiteRole;

return [
    /*
    |--------------------------------------------------------------------------
    | ACL
    |--------------------------------------------------------------------------
    |
    | Site-wide access levels (users.access_level), the roles each level is
    | granted, and the controller actions each role opens up. Anything not
    | listed here is denied (404). Per-site roles (SiteRole) narrow this
    | further inside a site, see `siteRoles` below.
    |
    | Routes with no controller (closures, Route::view) can never be allowed,
    | so every authenticated route needs a controller.
    |
    */

    'accessLevels' => [
        AccessLevel::ADMIN => [
            'common',
            'adminManager',
            'siteContent',
            'siteManage',
            'siteOwner',
        ],
        AccessLevel::CUSTOMER => [
            'common',
            'siteContent',
            'siteManage',
            'siteOwner',
        ],
    ],

    // What a SiteRole may do inside a site: the site* roles below it is
    // granted. Applies to customers only; platform admins can do all of them.
    'siteRoles' => [
        SiteRole::OWNER => ['siteContent', 'siteManage', 'siteOwner'],
        SiteRole::ADMIN => ['siteContent', 'siteManage'],
        SiteRole::EDITOR => ['siteContent'],
    ],

    'roles' => [
        'common' => [
            [HomeController::class, '__invoke'],
            [AccountController::class, 'edit'],
            [AccountController::class, 'update'],
        ],

        // The whole-CMS area under /admin. Extend with sites, users, billing, ...
        'adminManager' => [
            [AdminDashboardController::class, 'index'],
            [AdminSiteController::class, 'index'],
            [AdminSiteController::class, 'create'],
            [AdminSiteController::class, 'store'],
            [AdminSiteController::class, 'show'],
            [AdminSiteController::class, 'update'],
            [AdminSiteController::class, 'destroy'],
            [AdminSiteMemberController::class, 'store'],
            [AdminSiteMemberController::class, 'destroy'],
            [AdminSiteTokenController::class, 'store'],
            [AdminSiteTokenController::class, 'destroy'],
            [AdminUserController::class, 'index'],
            [AdminUserController::class, 'create'],
            [AdminUserController::class, 'store'],
            [AdminUserController::class, 'show'],
            [AdminUserController::class, 'update'],
            [AdminUserController::class, 'resetPassword'],
            [AdminUserController::class, 'toggleDisabled'],
            [AdminUserController::class, 'destroy'],
        ],

        // Day-to-day content work inside one site: /{site}/... (every member).
        'siteContent' => [
            [SiteDashboardController::class, 'index'],
            [PageController::class, 'index'],
            [PageController::class, 'show'],
            [PageController::class, 'edit'],
            [CollectionController::class, 'index'],
            [CollectionController::class, 'show'],
            [CollectionController::class, 'create'],
            [CollectionController::class, 'edit'],
            [MediaController::class, 'index'],
            [MediaController::class, 'store'],
            [MediaController::class, 'update'],
            [MediaController::class, 'replace'],
            [MediaController::class, 'bulkMove'],
            [MediaFolderController::class, 'store'],
            [MediaFolderController::class, 'update'],
            [MediaFolderController::class, 'destroy'],
        ],

        // Destructive or people-related work inside one site (owner and admin).
        'siteManage' => [
            [MediaController::class, 'destroy'],
            [MediaController::class, 'bulkDestroy'],
            [HistoryController::class, 'index'],
            [SiteUserController::class, 'index'],
            [SiteUserController::class, 'create'],
            [SiteUserController::class, 'store'],
            [SiteUserController::class, 'show'],
            [SiteUserController::class, 'update'],
            [SiteUserController::class, 'resetPassword'],
            [SiteUserController::class, 'destroy'],
        ],

        // Only a site's owners.
        'siteOwner' => [
            [SiteUserController::class, 'role'],
        ],
    ],
];
