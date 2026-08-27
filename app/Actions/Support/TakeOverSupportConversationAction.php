<?php

namespace App\Actions\Support;

use App\Enums\SupportConversationMode;
use App\Enums\SupportConversationStatus;
use App\Models\SupportAssignment;
use App\Models\SupportConversation;
use App\Models\SupportEvent;
use App\Models\User;
use App\Services\Ai\SupportAiBatchService;
use App\Services\Support\SupportRealtimeService;
use Illuminate\Support\Facades\DB;

class TakeOverSupportConversationAction
{
    public function execute(SupportConversation $conversation, User $admin): SupportConversation
    {
        $result = DB::transaction(function () use ($conversation, $admin) {
            $locked = SupportConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            SupportAssignment::query()->where('conversation_id', $locked->id)->whereNull('ended_at')->update(['ended_at' => now()]);
            $locked->update(['mode' => SupportConversationMode::HUMAN_ACTIVE, 'status' => SupportConversationStatus::OPEN, 'assigned_admin_id' => $admin->id, 'taken_over_at' => now()]);
            SupportAssignment::create(['conversation_id' => $locked->id, 'admin_id' => $admin->id, 'assigned_by' => $admin->id, 'assigned_at' => now()]);
            SupportEvent::create(['conversation_id' => $locked->id, 'event_type' => 'HUMAN_TAKEOVER', 'actor_type' => 'ADMIN', 'actor_id' => $admin->id]);

            return $locked->fresh();
        });
        app(SupportAiBatchService::class)->cancelConversation($conversation->id);
        app(SupportRealtimeService::class)->changed($conversation->id, 'conversation.mode_changed');

        return $result;
    }
}
