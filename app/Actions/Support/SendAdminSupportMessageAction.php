<?php

namespace App\Actions\Support;

use App\Enums\SupportConversationMode;
use App\Enums\SupportConversationStatus;
use App\Models\SupportConversation;
use App\Models\SupportEvent;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\Support\SupportRealtimeService;

class SendAdminSupportMessageAction
{
    public function execute(SupportConversation $conversation, User $admin, string $content): SupportMessage
    {
        $message = DB::transaction(function () use ($conversation, $admin, $content) {
            $locked = SupportConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            if ($locked->mode !== SupportConversationMode::HUMAN_ACTIVE) {
                app(TakeOverSupportConversationAction::class)->execute($locked, $admin);
                $locked->refresh();
            }
            $message = SupportMessage::create(['conversation_id' => $locked->id, 'sender_type' => 'ADMIN', 'sender_user_id' => $admin->id, 'content' => $content, 'delivery_status' => 'SENT']);
            $locked->update(['status' => SupportConversationStatus::OPEN, 'last_message_at' => now(), 'last_admin_message_at' => now(), 'customer_unread_count' => $locked->customer_unread_count + 1]);
            SupportEvent::create(['conversation_id' => $locked->id, 'event_type' => 'ADMIN_REPLIED', 'actor_type' => 'ADMIN', 'actor_id' => $admin->id, 'metadata' => ['message_id' => $message->id]]);
            return $message;
        });
        app(SupportRealtimeService::class)->changed($conversation->id, 'message.created');
        return $message;
    }
}
