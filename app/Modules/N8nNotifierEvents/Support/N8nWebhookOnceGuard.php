<?php

namespace App\Modules\N8nNotifierEvents\Support;

class N8nWebhookOnceGuard
{
    /**
     * Evita mandar el webhook más de una vez por request y por clave.
     * Ejemplos de clave:
     * - tenant:{tenantJid}
     * - lead:{leadJid}
     */
    private static array $sent = [];

    public static function shouldSend(string $key): bool
    {
        $key = trim($key);

        if ($key === '') {
            return false;
        }

        if (isset(self::$sent[$key])) {
            return false;
        }

        self::$sent[$key] = true;

        return true;
    }
}
