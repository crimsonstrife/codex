<?php

use App\Http\Controllers\Api\PageSearchApiController;
use App\Http\Controllers\Api\WorkspaceApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Forge Integration API (machine-to-machine, app-level token)
|--------------------------------------------------------------------------
|
| These endpoints are called by Forge using a system-level AppToken — not
| tied to any user account. Generate a token with:
|
|   php artisan app-token:create "Forge"
|
| Then set the printed value as CODEX_APP_TOKEN in Forge's .env file.
|
*/
Route::prefix('v1')->middleware(['auth.app_token', 'throttle:api'])->group(function () {
    // List Codex workspaces for Forge's workspace selector
    Route::get('workspaces', [WorkspaceApiController::class, 'index'])
        ->name('api.v1.workspaces.index');

    // Search Codex pages for Forge's issue page-link feature
    Route::get('pages/search', [PageSearchApiController::class, 'search'])
        ->name('api.v1.pages.search');
});
