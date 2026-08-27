<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SupportConversation;
use App\Models\SupportEvent;
use App\Models\SupportMessage;
use App\Models\SupportMessageAction;
use App\Services\Support\SupportCustomerAuthenticator;
use App\Services\Support\SupportRealtimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportCommerceController extends Controller
{
    public function action(Request $request, SupportConversation $conversation, SupportCustomerAuthenticator $authenticator): JsonResponse
    {
        $conversation->load('customer');
        $authenticator->authorize($request, $conversation);
        $data = $request->validate([
            'action' => ['required', 'in:SELECT_VARIANT,ADD_TO_CART,BUY_NOW'],
            'product_id' => ['required', 'integer'], 'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'message_id' => ['nullable', 'uuid'], 'displayed_price_centavos' => ['nullable', 'integer', 'min:0'],
            'idempotency_key' => ['required', 'string', 'max:120'],
        ]);

        $existing = SupportMessageAction::where('conversation_id', $conversation->id)->where('idempotency_key', $data['idempotency_key'])->first();
        if ($existing) return response()->json(['data' => $existing->result_metadata + ['status' => $existing->status], 'idempotent' => true]);

        $variant = Product::query()->with(['inventory', 'storefrontProduct'])->where('id', $data['variant_id'])->where('storefront_product_id', $data['product_id'])->where('status', 'active')->first();
        $status = 'SUCCESS'; $result = [];
        if (! $variant || ! $variant->storefrontProduct || $variant->storefrontProduct->status !== 'active') {
            $status = 'INVALID_VARIANT';
        } elseif (($variant->inventory?->current_stock ?? 0) < $data['quantity']) {
            $status = 'OUT_OF_STOCK';
        } elseif (isset($data['displayed_price_centavos']) && (int) round($variant->selling_price * 100) !== $data['displayed_price_centavos']) {
            $status = 'PRICE_CHANGED';
        }
        if ($variant) {
            $result = ['action' => $data['action'], 'product_id' => (string) $variant->storefront_product_id, 'variant_id' => (string) $variant->id, 'product_name' => $variant->product_name, 'size' => $variant->size, 'color' => $variant->color, 'quantity' => $data['quantity'], 'price_centavos' => (int) round($variant->selling_price * 100), 'stock' => $variant->inventory?->current_stock ?? 0];
        }

        $action = DB::transaction(function () use ($conversation, $data, $status, $result) {
            $action = SupportMessageAction::create(['conversation_id' => $conversation->id, 'message_id' => $data['message_id'] ?? null, 'customer_id' => $conversation->customer_id, 'idempotency_key' => $data['idempotency_key'], 'action_type' => $data['action'], 'product_id' => $data['product_id'], 'variant_id' => $data['variant_id'], 'quantity' => $data['quantity'], 'status' => $status, 'result_metadata' => $result]);
            $context = array_merge($conversation->context ?? [], ['active_product_id' => (string) $data['product_id'], 'active_variant_id' => (string) $data['variant_id'], 'last_product_action' => $data['action'], 'last_product_action_status' => $status, 'active_cart_quantity' => $status === 'SUCCESS' && $data['action'] !== 'SELECT_VARIANT' ? $data['quantity'] : null]);
            $conversation->update(['context' => $context, 'last_message_at' => now(), 'admin_unread_count' => $conversation->admin_unread_count + 1]);
            $successText = $data['action'] === 'SELECT_VARIANT'
                ? "Selected {$result['color']} / {$result['size']}."
                : "{$result['product_name']} ({$result['color']} / {$result['size']}) — quantity {$data['quantity']} ".($data['action'] === 'BUY_NOW' ? 'ready for checkout.' : 'added to cart.');
            $text = $status === 'SUCCESS' ? $successText : ($status === 'OUT_OF_STOCK' ? 'This variant is currently out of stock.' : ($status === 'PRICE_CHANGED' ? 'The price has changed. Please review the updated price before continuing.' : 'That product variant is no longer available.'));
            SupportMessage::create(['conversation_id' => $conversation->id, 'sender_type' => 'SYSTEM', 'content_type' => 'ACTION_RESULT', 'content' => $text, 'payload' => $result + ['action' => $data['action'], 'status' => $status], 'delivery_status' => 'SENT']);
            SupportEvent::create(['conversation_id' => $conversation->id, 'event_type' => 'COMMERCE_'.$data['action'], 'actor_type' => 'CUSTOMER', 'metadata' => $result + ['status' => $status, 'action_id' => $action->id]]);
            return $action;
        });
        app(SupportRealtimeService::class)->changed($conversation->id, 'commerce.action');
        return response()->json(['data' => $action->result_metadata + ['status' => $action->status]]);
    }
}
