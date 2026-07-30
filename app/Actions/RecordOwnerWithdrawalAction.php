<?php

namespace App\Actions;

use App\Models\CashTransaction;
use App\Models\OwnerDrawal;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecordOwnerWithdrawalAction
{
    public static function execute(array $data, ?User $user = null): OwnerDrawal
    {
        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw new Exception("Owner withdrawal amount must be greater than zero.");
        }

        $actor = $user ?? Auth::user();
        if (! $actor || ! $actor->canRecordWithdrawals()) {
            throw new Exception('Only the Owner can record owner withdrawals.');
        }
        $userId = $actor->id;

        if (! empty($data['client_uuid']) && ($existing = OwnerDrawal::where('client_uuid', $data['client_uuid'])->first())) {
            return $existing;
        }

        return DB::transaction(function () use ($data, $amount, $userId) {
            // FIX: Create drawal first, use real auto-increment ID for drawal number
            $drawal = OwnerDrawal::create([
                'drawal_number' => 'PENDING',
                'client_uuid'   => $data['client_uuid'] ?? null,
                'user_id'       => $userId,
                'amount'        => $amount,
                'drawal_date'   => $data['drawal_date'] ?? Carbon::today(),
                'reason'        => $data['reason'] ?? 'Owner Cash Withdrawal',
                'payment_source'=> $data['payment_source'] ?? 'cash',
                'remarks'       => $data['remarks'] ?? null,
                'status'        => 'completed',
                'record_version'=> 1,
                'server_updated_at' => now(),
                'sync_source'   => $data['sync_source'] ?? 'online',
                'created_by'    => $userId,
                'updated_by'    => $userId,
            ]);

            $drawalNumber = 'DRW-' . str_pad($drawal->id, 6, '0', STR_PAD_LEFT);
            $drawal->update(['drawal_number' => $drawalNumber]);

            // Create Cash Transaction (negative amount, type owner_withdrawal)
            $cashTx = CashTransaction::create([
                'transaction_number' => 'PENDING',
                'user_id'            => $userId,
                'type'               => 'owner_withdrawal',
                'direction'          => 'cash_out',
                'amount'             => -$amount,
                'owner_drawal_id'    => $drawal->id,
                'description'        => "Owner Withdrawal {$drawalNumber}: {$drawal->reason}",
                'transaction_date'   => Carbon::now(),
                'sync_source'        => $data['sync_source'] ?? 'online',
                'created_by'         => $userId,
                'updated_by'         => $userId,
            ]);
            $cashTx->update(['transaction_number' => 'CTX-' . str_pad($cashTx->id, 6, '0', STR_PAD_LEFT)]);

            ActivityLogService::log(
                'Owner Withdrawal Recorded',
                "Recorded owner cash withdrawal {$drawalNumber} of ₱" . number_format($amount, 2) . ". (Note: Does not affect operating profit)",
                $drawal,
                ['client_uuid' => $drawal->client_uuid, 'sync_source' => $drawal->sync_source]
            );

            return $drawal;
        });
    }
}
