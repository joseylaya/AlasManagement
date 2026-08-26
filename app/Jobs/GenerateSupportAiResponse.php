<?php

namespace App\Jobs;

use App\Models\SupportMessage;
use App\Services\Ai\AiResponseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateSupportAiResponse implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public string $messageId) {}

    public function handle(AiResponseService $service): void
    {
        $message = SupportMessage::find($this->messageId);
        if ($message) $service->respondTo($message);
    }
}
