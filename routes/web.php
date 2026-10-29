<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SiteController as AdminSiteController;
use App\Http\Controllers\Admin\SiteMemberController as AdminSiteMemberController;
use App\Http\Controllers\Admin\SiteTokenController as AdminSiteTokenController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Site\CollectionController;
use App\Http\Controllers\Site\DashboardController as SiteDashboardController;
use App\Http\Controllers\Site\HistoryController;
use App\Http\Controllers\Site\MediaController;
use App\Http\Controllers\Site\MediaFolderController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\UserController as SiteUserController;
use App\Http\Middleware\EnforceAcl;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetCurrentSite;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', EnsureUserIsActive::class, EnforceAcl::class])->group(function () {
    // Landing
    Route::get('/', HomeController::class)->name('home');

    // Signed-in user's own account
    Route::prefix('account')->name('account.')->controller(AccountController::class)->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::post('/', 'update')->name('update');
    });

    // Admin Panel
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('sites')->name('sites.')->group(function () {
            Route::controller(AdminSiteController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{site:slug}', 'show')->name('show');
                Route::post('/{site:slug}', 'update')->name('update');
                Route::delete('/{site:slug}', 'destroy')->name('destroy');
            });

            Route::controller(AdminSiteMemberController::class)->group(function () {
                Route::post('/{site:slug}/members', 'store')->name('members.store');
                Route::delete('/{site:slug}/members/{user}', 'destroy')->name('members.destroy');
            });

            Route::controller(AdminSiteTokenController::class)->group(function () {
                Route::post('/{site:slug}/tokens', 'store')->name('tokens.store');
                Route::delete('/{site:slug}/tokens/{token}', 'destroy')->name('tokens.destroy');
            });
        });

        Route::prefix('users')->name('users.')->controller(AdminUserController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{user}', 'show')->name('show');
            Route::post('/{user}', 'update')->name('update');
            Route::post('/{user}/password', 'resetPassword')->name('password');
            Route::post('/{user}/disabled', 'toggleDisabled')->name('disabled');
            Route::delete('/{user}', 'destroy')->name('destroy');
        });
    });

    // Sites
    Route::prefix('{site}')->middleware(SetCurrentSite::class)->group(function () {
        Route::get('/dashboard', [SiteDashboardController::class, 'index'])->name('dashboard');

        Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
        Route::get('/pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');
        Route::get('/pages/{page:slug}/edit', [PageController::class, 'edit'])->name('pages.edit');

        Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');

        Route::prefix('collections/{collection:key}')->name('collections.')->controller(CollectionController::class)->group(function () {
            Route::get('/', 'show')->name('show');
            Route::get('/entries/create', 'create')->name('create');
            Route::get('/entries/{entry}/edit', 'edit')->name('edit');
        });

        Route::prefix('media')->name('media.')->controller(MediaController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::post('/bulk-move', 'bulkMove')->name('bulk-move');
            Route::post('/bulk-delete', 'bulkDestroy')->name('bulk-delete');
            Route::post('/{media}', 'update')->name('update');
            Route::post('/{media}/replace', 'replace')->name('replace');
            Route::delete('/{media}', 'destroy')->name('destroy');
        });

        Route::prefix('media-folders')->name('media-folders.')->controller(MediaFolderController::class)->group(function () {
            Route::post('/', 'store')->name('store');
            Route::post('/{folder}', 'update')->name('update');
            Route::delete('/{folder}', 'destroy')->name('destroy');
        });

        Route::get('/history', [HistoryController::class, 'index'])->name('history.index');

        Route::prefix('users')->name('users.')->controller(SiteUserController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{user}', 'show')->name('show');
            Route::post('/{user}', 'update')->name('update');
            Route::post('/{user}/role', 'role')->name('role');
            Route::post('/{user}/password', 'resetPassword')->name('password');
            Route::delete('/{user}', 'destroy')->name('destroy');
        });
    });
});
