<?php

use App\Http\Controllers\Webhook\EspoCrmWebhookController;
use Illuminate\Support\Facades\Route;

// ... Rutas EspoCRM ...
Route::post('/espocrm/account-updated', [EspoCrmWebhookController::class, 'accountUpdated']);
Route::post('/espocrm/opportunity-updated', [EspoCrmWebhookController::class, 'opportunityUpdated']);
Route::post('/espocrm/service-created', [EspoCrmWebhookController::class, 'serviceCreated']);
Route::post('/espocrm/service-updated', [EspoCrmWebhookController::class, 'serviceUpdated']);
Route::post('/espocrm/staff-created', [EspoCrmWebhookController::class, 'staffCreated']);
Route::post('/espocrm/staff-updated', [EspoCrmWebhookController::class, 'staffUpdated']);
