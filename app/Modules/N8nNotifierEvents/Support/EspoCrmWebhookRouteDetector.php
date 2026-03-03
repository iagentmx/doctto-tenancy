<?php

namespace App\Modules\N8nNotifierEvents\Support;

use Illuminate\Support\Str;

class EspoCrmWebhookRouteDetector
{
    /**
     * Detecta si el request actual es uno de los webhooks *-created/*-updated de EspoCRM.
     * Soporta prefijos como /webhook/... o cualquier otro (usamos endsWith).
     */
    public static function shouldNotify(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        $req = request();
        if (! $req) {
            return false;
        }

        $path = trim((string) $req->path(), '/');

        return Str::endsWith($path, [
            'espocrm/account-updated',
            'espocrm/opportunity-updated',
            'espocrm/service-created',
            'espocrm/service-updated',
            'espocrm/staff-created',
            'espocrm/staff-updated',
        ]);
    }
}
