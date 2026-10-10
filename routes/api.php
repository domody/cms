<?php

use App\Http\Controllers\Api\BlockDefinitionController;
use App\Http\Controllers\Api\PageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('api.pages.show');
    Route::put('/block-definitions', [BlockDefinitionController::class, 'sync'])->name('api.block-definitions.sync');
});
