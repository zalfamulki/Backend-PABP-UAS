<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\WebPush;

class GenerateVapidKeys extends Command
{
    protected $signature = 'vapid:generate';
    protected $description = 'Generate VAPID keys for Web Push Notifications';

    public function handle()
    {
        $keys = WebPush::generateVAPIDKeys();

        $this->info('VAPID Keys generated successfully!');
        $this->warn('Add these to your .env file:');
        $this->line("VAPID_PUBLIC_KEY={$keys['publicKey']}");
        $this->line("VAPID_PRIVATE_KEY={$keys['privateKey']}");
    }
}
