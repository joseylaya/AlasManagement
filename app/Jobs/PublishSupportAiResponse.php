<?php

namespace App\Jobs;

use App\Models\SupportAiJob;
use App\Services\Ai\AiResponseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishSupportAiResponse implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [1, 3, 5];

    public function __construct(public string $supportAiJobId) {}

    public function handle(AiResponseService $service): void
    {
        $batch = SupportAiJob::find($this->supportAiJobId);
        if ($batch) {
            $service->publish($batch);
        }
    }
}
