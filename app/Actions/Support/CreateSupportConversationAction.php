<?php

namespace App\Actions\Support;

use App\Enums\SupportConversationMode;
use App\Enums\SupportConversationStatus;
use App\Models\AiSetting;
use App\Models\SupportConversation;
use App\Models\SupportCustomer;
use App\Models\SupportEvent;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSupportConversationAction
{
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $token = Str::random(64);
            $customer = SupportCustomer::create([
                'visitor_id' => Str::uuid(),
                'access_token_hash' => hash('sha256', $token),
                'display_name' => $data['display_name'] ?? null,
                'email' => $data['email'] ?? null,
            ]);
            $settings = AiSetting::query()->first();
            $mode = $settings?->enabled ? SupportConversationMode::AI_ACTIVE : SupportConversationMode::AI_PAUSED;
            $conversation = SupportConversation::create([
                'customer_id' => $customer->id,
                'mode' => $mode,
                'status' => SupportConversationStatus::OPEN,
                'context' => array_filter($data['context'] ?? []),
            ]);
            SupportEvent::create(['conversation_id' => $conversation->id, 'event_type' => 'CONVERSATION_CREATED', 'actor_type' => 'CUSTOMER']);

            if (filled($settings?->welcome_message)) {
                SupportMessage::create(['conversation_id' => $conversation->id, 'sender_type' => 'SYSTEM', 'content' => $settings->welcome_message, 'delivery_status' => 'SENT']);
            }

            return ['conversation' => $conversation->fresh('messages'), 'token' => $token];
        });
    }
}
