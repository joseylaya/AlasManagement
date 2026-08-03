<?php
namespace App\Actions\Finance;
use App\Models\{CashTransaction,OwnerCapitalInjection,User}; use App\Services\ActivityLogService; use Illuminate\Support\Facades\DB; use Illuminate\Validation\ValidationException;
class ReverseCapitalInjectionAction {
 public static function execute(OwnerCapitalInjection $capital, string $reason, User $actor, bool $allowNegative=false): OwnerCapitalInjection {
  if (!$actor->can('reverse',$capital)) throw ValidationException::withMessages(['capital'=>'Only the Owner can reverse a posted capital injection.']);
  if (trim($reason)==='') throw ValidationException::withMessages(['reason'=>'A reversal reason is required.']);
  return DB::transaction(function() use($capital,$reason,$actor,$allowNegative) { $capital->refresh(); if($capital->status!=='posted') return $capital; $balance=(float) CashTransaction::where('financial_account_id',$capital->financial_account_id)->sum('amount'); if($balance < (float)$capital->amount && !$allowNegative) throw ValidationException::withMessages(['reason'=>'This account has only ₱'.number_format($balance,2).'. Add an override reason to allow a negative balance.']);
   $tx=CashTransaction::create(['transaction_number'=>'PENDING','client_uuid'=>null,'user_id'=>$actor->id,'type'=>'capital_injection_reversal','direction'=>'cash_out','amount'=>-$capital->amount,'financial_account_id'=>$capital->financial_account_id,'description'=>"Reversal of {$capital->capital_injection_number}: {$reason}",'transaction_date'=>now(),'sync_source'=>'online','created_by'=>$actor->id,'updated_by'=>$actor->id]); $tx->update(['transaction_number'=>'CTX-'.str_pad($tx->id,6,'0',STR_PAD_LEFT)]);
   $capital->update(['status'=>'reversed','reversed_by'=>$actor->id,'reversed_at'=>now(),'reversal_reason'=>$reason,'updated_by'=>$actor->id]);
   DB::table('finance_ledger_entries')->insert([['capital_injection_id'=>$capital->id,'account'=>'Owner Contributed Capital','entry_type'=>'debit','amount'=>$capital->amount,'created_at'=>now(),'updated_at'=>now()],['capital_injection_id'=>$capital->id,'account'=>$capital->account->name,'entry_type'=>'credit','amount'=>$capital->amount,'created_at'=>now(),'updated_at'=>now()]]);
   ActivityLogService::log('Capital Injection Reversed',"Reversed {$capital->capital_injection_number}: ₱".number_format($capital->amount,2).'.',$capital,['amount'=>$capital->amount,'previous_status'=>'posted','new_status'=>'reversed','reversal_reason'=>$reason],$actor); return $capital->fresh('account');
  });
 }
}
