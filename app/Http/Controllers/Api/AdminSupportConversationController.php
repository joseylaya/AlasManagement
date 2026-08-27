<?php

namespace App\Http\Controllers\Api;

use App\Actions\Support\ResumeSupportAiAction;
use App\Actions\Support\SendAdminSupportMessageAction;
use App\Actions\Support\TakeOverSupportConversationAction;
use App\Enums\SupportConversationMode;
use App\Enums\SupportConversationStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSupportConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SupportConversation::query()->with('customer:id,display_name,email')->with(['messages' => fn ($q) => $q->orderByDesc('id')->limit(1)])->orderByDesc('last_message_at');
        if ($request->filled('mode')) $query->where('mode', $request->string('mode'));
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q->whereHas('customer', fn ($customer) => $customer->where('display_name', 'like', $term)->orWhere('email', 'like', $term))->orWhereHas('messages', fn ($message) => $message->where('content', 'like', $term)));
        }
        return response()->json($query->paginate(30));
    }

    public function show(SupportConversation $conversation): JsonResponse
    {
        $conversation->update(['admin_unread_count' => 0]);
        $conversation->load([
            'customer',
            'assignedAdmin:id,name',
            'messages' => fn ($query) => $query->orderBy('id')->with('senderUser:id,name'),
        ]);

        return response()->json(['data' => $conversation]);
    }

    public function send(Request $request, SupportConversation $conversation, SendAdminSupportMessageAction $action): JsonResponse
    {
        $data = $request->validate(['content' => ['required', 'string', 'max:2000']]);
        return response()->json(['data' => $action->execute($conversation, $request->user(), trim($data['content']))], 201);
    }

    public function takeover(Request $request, SupportConversation $conversation, TakeOverSupportConversationAction $action): JsonResponse
    {
        return response()->json(['data' => $action->execute($conversation, $request->user())]);
    }

    public function resume(Request $request, SupportConversation $conversation, ResumeSupportAiAction $action): JsonResponse
    {
        return response()->json(['data' => $action->execute($conversation, $request->user())]);
    }

    public function resolve(Request $request, SupportConversation $conversation): JsonResponse
    {
        $result = DB::transaction(function () use ($request, $conversation) {
            $locked = SupportConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            $locked->update(['mode' => SupportConversationMode::RESOLVED, 'status' => SupportConversationStatus::RESOLVED, 'resolved_at' => now()]);
            SupportEvent::create(['conversation_id' => $locked->id, 'event_type' => 'CONVERSATION_RESOLVED', 'actor_type' => 'ADMIN', 'actor_id' => $request->user()->id]);
            return $locked->fresh();
        });
        return response()->json(['data' => $result]);
    }
}
