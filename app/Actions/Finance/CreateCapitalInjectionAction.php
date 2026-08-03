<?php
namespace App\Actions\Finance;
use App\Models\{CashTransaction,FinancialAccount,OwnerCapitalInjection,User}; use App\Services\ActivityLogService; use Illuminate\Support\Facades\DB; use Illuminate\Support\Str; use Illuminate\Validation\ValidationException;
class CreateCapitalInjectionAction {
 public static function execute(array $data, User $actor): OwnerCapitalInjection {
  if (!$actor->isOwner()) throw ValidationException::withMessages(['capital'=>'Only the Owner can add capital.']);
  if ($existing=OwnerCapitalInjection::where('client_uuid',$data['client_uuid'])->first()) return $existing;
  $account=FinancialAccount::whereKey($data['financial_account_id'])->where('is_active',true)->firstOrFail();
  return DB::transaction(function() use($data,$actor,$account) {
   if ($existing=OwnerCapitalInjection::where('client_uuid',$data['client_uuid'])->lockForUpdate()->first()) return $existing;
   $capital=OwnerCapitalInjection::create(['capital_injection_number'=>'PENDING','client_uuid'=>$data['client_uuid'],'owner_user_id'=>$actor->id,'amount'=>$data['amount'],'financial_account_id'=>$account->id,'funding_source'=>$data['funding_source'],'contribution_date'=>$data['contribution_date'],'reference_number'=>$data['reference_number']??null,'description'=>$data['description']??null,'remarks'=>$data['remarks']??null,'proof_path'=>$data['proof_path']??null,'created_by'=>$actor->id,'updated_by'=>$actor->id]);
   $capital->update(['capital_injection_number'=>'CAP-'.now()->format('Y').'-'.str_pad($capital->id,5,'0',STR_PAD_LEFT)]);
   $tx=CashTransaction::create(['transaction_number'=>'PENDING','client_uuid'=>$capital->client_uuid,'user_id'=>$actor->id,'type'=>'capital_injection','direction'=>'cash_in','amount'=>$capital->amount,'financial_account_id'=>$account->id,'description'=>"Capital Added {$capital->capital_injection_number} to {$account->name}".($capital->reference_number ? " · {$capital->reference_number}" : ''),'transaction_date'=>$capital->contribution_date,'sync_source'=>'online','created_by'=>$actor->id,'updated_by'=>$actor->id]);
   $tx->update(['transaction_number'=>'CTX-'.str_pad($tx->id,6,'0',STR_PAD_LEFT)]); $capital->update(['cash_transaction_id'=>$tx->id]);
   DB::table('finance_ledger_entries')->insert([['capital_injection_id'=>$capital->id,'account'=>$account->name,'entry_type'=>'debit','amount'=>$capital->amount,'created_at'=>now(),'updated_at'=>now()],['capital_injection_id'=>$capital->id,'account'=>'Owner Contributed Capital','entry_type'=>'credit','amount'=>$capital->amount,'created_at'=>now(),'updated_at'=>now()]]);
   ActivityLogService::log('Capital Injection Posted',"{$capital->capital_injection_number}: ₱".number_format($capital->amount,2)." added to {$account->name}.",$capital,['amount'=>$capital->amount,'destination_account'=>$account->name,'funding_source'=>$capital->funding_source,'reference_number'=>$capital->reference_number,'previous_status'=>null,'new_status'=>'posted'],$actor);
   return $capital->fresh('account');
  });
 }
}
