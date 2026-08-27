<?php

namespace App\Http\Controllers\Api;

use App\Actions\Support\CreateSupportConversationAction;
use App\Actions\Support\SendCustomerSupportMessageAction;
use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\AiRun;
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
        if ($request->filled('after')) $query->where('created_at', '>', $request->date('after'));
        $messages = $query->limit(50)->get()->reverse()->values();
        return response()->json(['data' => $messages]);
    }

    public function send(Request $request, SupportConversation $conversation, SupportCustomerAuthenticator $authenticator, SendCustomerSupportMessageAction $action): JsonResponse
    {
        $conversation->load('customer');
        $authenticator->authorize($request, $conversation);
        $data = $request->validate(['content' => ['required', 'string', 'max:2000'], 'client_message_id' => ['required', 'string', 'max:100']]);
        return response()->json(['data' => $action->execute($conversation, trim($data['content']), $data['client_message_id'])], 201);
    }

    private function conversation(SupportConversation $conversation): array
    {
        $latestCustomerMessage = $conversation->messages()->where('sender_type', 'CUSTOMER')->orderByDesc('id')->first();
        $latestRun = $latestCustomerMessage ? AiRun::where('trigger_message_id', $latestCustomerMessage->id)->first() : null;
        $isNewAfterResume = ! $conversation->ai_resumed_at || ($latestCustomerMessage?->created_at?->greaterThan($conversation->ai_resumed_at) ?? false);
        $aiPending = $conversation->mode->value === 'AI_ACTIVE'
            && $latestCustomerMessage
            && $isNewAfterResume
            && (! $latestRun || $latestRun->status === 'PROCESSING');

        return ['id' => $conversation->id, 'mode' => $conversation->mode->value, 'status' => $conversation->status->value, 'ai_pending' => (bool) $aiPending, 'context' => $conversation->context, 'last_message_at' => $conversation->last_message_at, 'messages' => $conversation->relationLoaded('messages') ? $conversation->messages : []];
    }
}
