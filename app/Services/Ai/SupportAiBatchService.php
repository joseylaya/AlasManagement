<?php

namespace App\Services\Ai;

use App\Jobs\GenerateSupportAiResponse;
use App\Models\SupportAiJob;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\DB;

class SupportAiBatchService
{
    private const ACTIVE = ['DEBOUNCING', 'QUEUED', 'PROCESSING', 'TYPING_DELAY'];

    public function add(SupportConversation $conversation, SupportMessage $message): SupportAiJob
    {
        [$batch, $created] = DB::transaction(function () use ($conversation, $message) {
            SupportConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            $batch = SupportAiJob::query()
                ->where('conversation_id', $conversation->id)
                ->where('status', 'DEBOUNCING')
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            $now = now();
            if (! $batch) {
                return [SupportAiJob::create([
                    'conversation_id' => $conversation->id,
                    'status' => 'DEBOUNCING',
                    'first_message_id' => $message->id,
                    'last_message_id' => $message->id,
                    'batch_started_at' => $now,
                    'ready_at' => $now->copy()->addMilliseconds(max(0, config('ai_chat.debounce_ms'))),
                ]), true];
            }

            $quietUntil = $now->copy()->addMilliseconds(max(0, config('ai_chat.debounce_ms')));
            $maximumWait = $batch->batch_started_at->copy()->addMilliseconds(max(0, config('ai_chat.max_batch_wait_ms')));
            $batch->update([
                'last_message_id' => $message->id,
                'ready_at' => $quietUntil->lessThan($maximumWait) ? $quietUntil : $maximumWait,
            ]);

            return [$batch->fresh(), false];
        });

        if ($created) {
            GenerateSupportAiResponse::dispatch($batch->id)->delay($batch->ready_at)->afterCommit();
        }

        return $batch;
    }

    public function cancelConversation(string $conversationId, string $reason = 'HUMAN_TAKEOVER'): int
    {
        return SupportAiJob::query()->where('conversation_id', $conversationId)->whereIn('status', self::ACTIVE)->update([
            'status' => 'CANCELLED',
            'finished_at' => now(),
            'error_code' => $reason,
        ]);
    }
}
