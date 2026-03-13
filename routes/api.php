<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\TenantStaffController;

Route::middleware('api-secure')->group(function () {
    Route::get('/v1/tenants/{tenantId}/catalog', [TenantController::class, 'catalog']);
    Route::get('/v1/tenants/{tenantJid}', [TenantController::class, 'show']);
    Route::get('/v1/tenants/by-espocrm-id/{espocrmId}', [TenantController::class, 'showByEspoCrmId']);
    Route::get('/v1/tenants/{tenantJid}/staff', [TenantStaffController::class, 'index']);
    Route::get('/v1/tenants/{tenantJid}/staff/{staffId}', [TenantStaffController::class, 'show']);
    Route::post('/v1/tenants/{tenantJid}/staff', [TenantStaffController::class, 'store']);
    Route::patch('/v1/tenants/{tenantJid}/staff/{staffId}', [TenantStaffController::class, 'update']);
    Route::delete('/v1/tenants/{tenantJid}/staff/{staffId}', [TenantStaffController::class, 'destroy']);
});
