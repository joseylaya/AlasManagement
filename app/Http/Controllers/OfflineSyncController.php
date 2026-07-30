<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrderAction;
use App\Actions\RecordOwnerWithdrawalAction;
use App\Models\Order;
use App\Models\OwnerDrawal;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfflineSyncController extends Controller
{
    public function order(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_uuid' => ['required', 'uuid'],
            'payload' => ['required', 'array'],
            'payload.items' => ['required', 'array', 'min:1'],
            'payload.items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'payload.items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($existing = Order::where('client_uuid', $data['client_uuid'])->first()) {
            return response()->json(['status' => 'synced', 'server_id' => $existing->id, 'record' => $existing]);
        }

        try {
            $payload = $data['payload'];
            $payload['client_uuid'] = $data['client_uuid'];
            $payload['sync_source'] = 'offline_sync';
            $order = CreateOrderAction::execute($payload, $payload['items'], $request->user());

            return response()->json(['status' => 'synced', 'server_id' => $order->id, 'record' => $order], 201);
        } catch (Exception $exception) {
            return response()->json(['status' => 'conflict', 'message' => 'The sale could not be synchronized. Please review stock availability and try again.'], 409);
        }
    }

    public function ownerWithdrawal(Request $request): JsonResponse
    {
        abort_unless($request->user()->canRecordWithdrawals(), 403);
        $data = $request->validate([
            'client_uuid' => ['required', 'uuid'],
            'payload.amount' => ['required', 'numeric', 'min:0.01'],
            'payload.drawal_date' => ['required', 'date'],
            'payload.reason' => ['required', 'string', 'max:255'],
            'payload.payment_source' => ['nullable', 'string', 'max:100'],
            'payload.remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($existing = OwnerDrawal::where('client_uuid', $data['client_uuid'])->first()) {
            return response()->json(['status' => 'synced', 'server_id' => $existing->id, 'record' => $existing]);
        }

        $payload = $request->input('payload');
        $payload['client_uuid'] = $data['client_uuid'];
        $payload['sync_source'] = 'offline_sync';
        $drawal = RecordOwnerWithdrawalAction::execute($payload, $request->user());

        return response()->json(['status' => 'synced', 'server_id' => $drawal->id, 'record' => $drawal], 201);
    }
}
