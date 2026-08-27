<?php

namespace App\Jobs;

use App\Enums\SupportConversationMode;
use App\Models\SupportAiJob;
use App\Services\Ai\AiResponseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GenerateSupportAiResponse implements ShouldQueue
{
    use Queueable;

    public int $tries = 0;

    public int $maxExceptions = 3;

    public function __construct(public string $supportAiJobId) {}

    public function handle(AiResponseService $service): void
    {
        $batch = SupportAiJob::find($this->supportAiJobId);
        if (! $batch || ! in_array($batch->status, ['DEBOUNCING', 'QUEUED'], true)) {
            return;
        }

        if ($batch->ready_at->isFuture()) {
            $this->release(max(1, now()->diffInSeconds($batch->ready_at, false)));

            return;
        }

        $batch->update(['status' => 'QUEUED']);
        if ((int) config('ai_chat.conversation_concurrency') < 1) {
            $this->release(5);

            return;
        }
        if ($this->hasEarlierTurn($batch) || $this->hasEarlierReadyJob($batch)) {
            $this->release(1);

            return;
        }

        $conversationLock = Cache::lock('ai-chat:conversation:'.$batch->conversation_id, max(10, config('ai_chat.lock_seconds')));
        if (! $conversationLock->get()) {
            $this->release(1);

            return;
        }

        $globalLock = $this->globalSlot();
        if (! $globalLock) {
            $conversationLock->release();
            $this->release(1);

            return;
        }

        try {
            $claimed = DB::transaction(function () use ($batch) {
                $locked = SupportAiJob::query()->lockForUpdate()->find($batch->id);
                if (! $locked || ! in_array($locked->status, ['DEBOUNCING', 'QUEUED'], true)) {
                    return false;
                }
                $conversation = $locked->conversation()->lockForUpdate()->firstOrFail();
                if ($conversation->mode !== SupportConversationMode::AI_ACTIVE) {
                    $locked->update(['status' => 'CANCELLED', 'finished_at' => now(), 'error_code' => 'AI_NOT_ACTIVE']);

                    return false;
                }
                $locked->update(['status' => 'PROCESSING', 'started_at' => now(), 'attempt_count' => $locked->attempt_count + 1]);

                return true;
            });
            if ($claimed) {
                $service->generate($batch->fresh());
            }
        } finally {
            $globalLock->release();
            $conversationLock->release();
        }
    }

    private function hasEarlierTurn(SupportAiJob $batch): bool
    {
        return SupportAiJob::query()->where('conversation_id', $batch->conversation_id)
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED', 'FAILED', 'RATE_LIMITED'])
            ->where(fn ($query) => $query->where('created_at', '<', $batch->created_at)->orWhere(fn ($same) => $same->where('created_at', $batch->created_at)->where('id', '<', $batch->id)))
            ->exists();
    }

    private function hasEarlierReadyJob(SupportAiJob $batch): bool
    {
        return SupportAiJob::query()
            ->whereKeyNot($batch->id)
            ->whereIn('status', ['DEBOUNCING', 'QUEUED'])
            ->where('ready_at', '<=', now())
            ->where(fn ($query) => $query->where('priority', '>', $batch->priority)
                ->orWhere(fn ($samePriority) => $samePriority->where('priority', $batch->priority)
                    ->where(fn ($older) => $older->where('created_at', '<', $batch->created_at)
                        ->orWhere(fn ($sameTime) => $sameTime->where('created_at', $batch->created_at)->where('id', '<', $batch->id)))))
            ->exists();
    }

    private function globalSlot(): mixed
    {
        foreach (range(1, max(1, config('ai_chat.global_concurrency'))) as $slot) {
            $lock = Cache::lock('ai-chat:global-slot:'.$slot, max(10, config('ai_chat.lock_seconds')));
            if ($lock->get()) {
                return $lock;
            }
        }

        return null;
    }
}
