<?php

use App\Http\Controllers\Api\BlockDefinitionController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\PageController;
use App\Http\Middleware\SetCurrentSiteFromToken;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

// Tokens are owned by a site and carry abilities: `read` serves content (a
// production frontend), `sync` also allows pushing the manifest (CI / dev).
// Abilities are checked after the request is placed inside the token's site,
// and requests are throttled per token.
Route::middleware(['auth:sanctum', SetCurrentSiteFromToken::class, 'throttle:cms-api'])->group(function () {
    Route::middleware(CheckForAnyAbility::class.':read,sync')->group(function () {
        Route::get('/pages/{slug}', [PageController::class, 'show'])->name('api.pages.show');
        Route::get('/collections/{key}', [CollectionController::class, 'index'])->name('api.collections.index');
        Route::get('/collections/{key}/{slug}', [CollectionController::class, 'show'])->name('api.collections.show');
    });

    Route::put('/block-definitions', [BlockDefinitionController::class, 'sync'])
        ->middleware(CheckAbilities::class.':sync')
        ->name('api.block-definitions.sync');
});
