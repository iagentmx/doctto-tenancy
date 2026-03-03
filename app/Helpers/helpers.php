<?php

/**
 * Convierte un correo de Cal.com SMS como:
 *
 *   "527712452798@sms.cal.com"
 *
 * A un JID de WhatsApp:
 *
 *   "5217712452798@s.whatsapp.net"
 *
 * Reglas:
 * - Si el email no contiene "@sms.cal.com" retorna null
 * - Si el número inicia con "52" → lo convierte a "521"
 */
if (!function_exists('sms_cal_to_jid')) {
    function sms_cal_to_jid(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@sms.cal.com')) {
            return null;
        }

        $number = explode('@', $email)[0];

        // Si viene como 52XXXXXXXXXX lo convertimos a 521XXXXXXXXXX
        if (strlen($number) === 12 && str_starts_with($number, '52')) {
            $number = '521' . substr($number, 2);
        }

        return $number . '@s.whatsapp.net';
    }
}
