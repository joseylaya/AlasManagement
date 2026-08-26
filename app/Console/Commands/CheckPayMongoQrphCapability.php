<?php

namespace App\Console\Commands;

use App\Services\PayMongoService;
use Illuminate\Console\Command;
use Throwable;

class CheckPayMongoQrphCapability extends Command
{
    protected $signature = 'paymongo:check-qrph';

    protected $description = 'Verify that the configured live PayMongo account exposes QR Ph';

    public function handle(PayMongoService $payMongo): int
    {
        try {
            $methods = $payMongo->assertLiveQrphCapability();
            $this->info('Live QR Ph capability is available.');
            $this->line('Available payment methods: '.implode(', ', $methods));
            return self::SUCCESS;
        } catch (Throwable $error) {
            $this->error($error->getMessage());
            return self::FAILURE;
        }
    }
}
