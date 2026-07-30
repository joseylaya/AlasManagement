<?php
namespace App\Actions;
use App\Models\ActivityLog; use App\Models\CashTransaction; use App\Models\CompensationRecord; use App\Models\User; use App\Services\ActivityLogService; use Illuminate\Support\Facades\DB; use Exception;
class PayCompensationAction {
 public static function execute(CompensationRecord $record, User $actor): CompensationRecord {
  if (!$actor->isOwner()) throw new Exception('Only the Owner can release compensation payments.');
  if ($record->status !== 'payable') throw new Exception('Only payable compensation can be released.');
  return DB::transaction(function() use($record,$actor) { if ($record->cash_transaction_id) return $record; $tx=CashTransaction::create(['transaction_number'=>'PENDING','user_id'=>$actor->id,'type'=>$record->type === 'salary' ? 'salary' : $record->type,'direction'=>'cash_out','amount'=>-$record->amount,'description'=>"{$record->type} payment for {$record->user->name} ({$record->record_number})",'transaction_date'=>now(),'sync_source'=>'online','created_by'=>$actor->id,'updated_by'=>$actor->id]); $tx->update(['transaction_number'=>'CTX-'.str_pad($tx->id,6,'0',STR_PAD_LEFT)]); $record->update(['status'=>'paid','paid_at'=>now(),'paid_by'=>$actor->id,'cash_transaction_id'=>$tx->id,'updated_by'=>$actor->id]); ActivityLogService::log('Compensation Paid',"Released {$record->type} payment of ₱".number_format($record->amount,2)." to {$record->user->name}.",$record,['compensation_type'=>$record->type,'amount'=>$record->amount,'previous_status'=>'payable','new_status'=>'paid'],$actor); return $record->fresh(); });
 }
}
