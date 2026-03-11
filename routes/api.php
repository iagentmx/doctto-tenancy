<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TenantController;

Route::middleware('api-secure')->group(function () {
    Route::get('/v1/tenants/{tenantId}/catalog', [TenantController::class, 'catalog']);
    Route::get('/v1/tenants/{tenantJid}', [TenantController::class, 'show']);
    Route::get('/v1/tenants/by-espocrm-id/{espocrmId}', [TenantController::class, 'showByEspoCrmId']);
});
