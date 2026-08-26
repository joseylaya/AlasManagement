<?php

namespace App\Actions\Support;

use App\Enums\SupportConversationMode;
use App\Enums\SupportConversationStatus;
use App\Jobs\GenerateSupportAiResponse;
use App\Models\SupportConversation;
use App\Models\SupportEvent;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\DB;
use App\Services\Support\SupportRealtimeService;

class SendCustomerSupportMessageAction
{
    public function execute(SupportConversation $conversation, string $content, string $clientMessageId): SupportMessage
    {
        [$message, $created] = DB::transaction(function () use ($conversation, $content, $clientMessageId) {
            $locked = SupportConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            $existing = SupportMessage::query()->where('conversation_id', $locked->id)->where('client_message_id', $clientMessageId)->first();
            if ($existing) return [$existing, false];

            if ($locked->mode === SupportConversationMode::RESOLVED) {
                $locked->mode = SupportConversationMode::AI_ACTIVE;
                $locked->status = SupportConversationStatus::OPEN;
                $locked->resolved_at = null;
            }
            $message = SupportMessage::create(['conversation_id' => $locked->id, 'sender_type' => 'CUSTOMER', 'content' => $content, 'client_message_id' => $clientMessageId, 'delivery_status' => 'SENT']);
            $locked->last_message_at = now();
            $locked->last_customer_message_at = now();
            $locked->admin_unread_count++;
            $locked->save();
            SupportEvent::create(['conversation_id' => $locked->id, 'event_type' => 'CUSTOMER_MESSAGE_CREATED', 'actor_type' => 'CUSTOMER', 'metadata' => ['message_id' => $message->id]]);
            return [$message, true];
        });

        if ($created && $conversation->fresh()->mode === SupportConversationMode::AI_ACTIVE) {
            GenerateSupportAiResponse::dispatch($message->id)->afterCommit();
        }
        if ($created) app(SupportRealtimeService::class)->changed($conversation->id, 'message.created');
        return $message;
    }
}
