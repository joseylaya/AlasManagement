<?php

namespace App\Http\Controllers\Api;

use App\Actions\Support\CreateSupportConversationAction;
use App\Actions\Support\SendCustomerSupportMessageAction;
use App\Http\Controllers\Controller;
use App\Models\SupportAiJob;
use App\Models\SupportConversation;
use App\Services\Support\SupportCustomerAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportConversationController extends Controller
{
    public function store(Request $request, CreateSupportConversationAction $action): JsonResponse
    {
        $data = $request->validate(['display_name' => ['nullable', 'string', 'max:100'], 'email' => ['nullable', 'email', 'max:255'], 'context' => ['nullable', 'array'], 'context.product_id' => ['nullable', 'integer'], 'context.variant_id' => ['nullable', 'integer'], 'context.product_slug' => ['nullable', 'string', 'max:255'], 'context.page_path' => ['nullable', 'string', 'max:500']]);
        $result = $action->execute($data);

        return response()->json(['data' => $this->conversation($result['conversation']), 'support_token' => $result['token']], 201);
    }

    public function show(Request $request, SupportConversation $conversation, SupportCustomerAuthenticator $authenticator): JsonResponse
    {
        $conversation->load('customer');
        $authenticator->authorize($request, $conversation);
        $conversation->update(['customer_unread_count' => 0]);
        $fresh = $conversation->fresh();
        $fresh->setRelation('messages', $fresh->messages()->orderByDesc('id')->limit(50)->get()->reverse()->values());

        return response()->json(['data' => $this->conversation($fresh)]);
    }

    public function messages(Request $request, SupportConversation $conversation, SupportCustomerAuthenticator $authenticator): JsonResponse
    {
        $conversation->load('customer');
        $authenticator->authorize($request, $conversation);
        $query = $conversation->messages()->orderByDesc('id');
        if ($request->filled('after')) {
            $query->where('created_at', '>', $request->date('after'));
        }
        $messages = $query->limit(50)->get()->reverse()->values();

        return response()->json(['data' => $messages]);
    }

    public function send(Request $request, SupportConversation $conversation, SupportCustomerAuthenticator $authenticator, SendCustomerSupportMessageAction $action): JsonResponse
    {
        $conversation->load('customer');
        $authenticator->authorize($request, $conversation);
        $data = $request->validate(['content' => ['required', 'string', 'max:'.max(1, config('ai_chat.max_message_chars'))], 'client_message_id' => ['required', 'string', 'max:100']]);

        return response()->json(['data' => $action->execute($conversation, trim($data['content']), $data['client_message_id'])], 201);
    }

    private function conversation(SupportConversation $conversation): array
    {
        $latestCustomerMessage = $conversation->messages()->where('sender_type', 'CUSTOMER')->orderByDesc('id')->first();
        $isNewAfterResume = ! $conversation->ai_resumed_at || ($latestCustomerMessage?->created_at?->greaterThan($conversation->ai_resumed_at) ?? false);
        $activeAiJob = SupportAiJob::query()->where('conversation_id', $conversation->id)
            ->whereIn('status', ['DEBOUNCING', 'QUEUED', 'PROCESSING', 'TYPING_DELAY'])
            ->latest('created_at')
            ->first();
        $aiPending = $conversation->mode->value === 'AI_ACTIVE'
            && $latestCustomerMessage
            && $isNewAfterResume
            && $activeAiJob;

        return ['id' => $conversation->id, 'mode' => $conversation->mode->value, 'status' => $conversation->status->value, 'ai_pending' => (bool) $aiPending, 'ai_status' => $aiPending ? $activeAiJob?->status : null, 'context' => $conversation->context, 'last_message_at' => $conversation->last_message_at, 'messages' => $conversation->relationLoaded('messages') ? $conversation->messages : []];
    }
}
