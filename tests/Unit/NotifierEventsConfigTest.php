<?php

namespace Tests\Unit;

use Tests\TestCase;

class NotifierEventsConfigTest extends TestCase
{
    public function test_it_parses_disabled_n8n_destination_as_false(): void
    {
        putenv('NOTIFIER_EVENTS_N8N_ENABLED=false');
        $_ENV['NOTIFIER_EVENTS_N8N_ENABLED'] = 'false';
        $_SERVER['NOTIFIER_EVENTS_N8N_ENABLED'] = 'false';

        $config = require base_path('config/notifier_events.php');

        $this->assertFalse($config['destinations']['n8n']['enabled']);
    }
}
