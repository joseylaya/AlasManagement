<?php

namespace App\Livewire\Finance;

use App\Actions\RecordExpenseAction;
use App\Actions\RecordOwnerWithdrawalAction;
use App\Models\CashTransaction;
use App\Models\ExpenseCategory;
use App\Models\CompensationRecord;
use App\Models\SalaryProfile;
use App\Models\User;
use App\Actions\PayCompensationAction;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Actions\Finance\CreateCapitalInjectionAction;
use App\Actions\Finance\ReverseCapitalInjectionAction;
use App\Models\FinancialAccount;
use App\Models\OwnerCapitalInjection;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

use App\Services\FinanceService;
use Exception;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public string $selectedType = '';
    public string $compensationStatus = '';
    public ?int $highlightCompensationId = null;

    // Record Expense Modal State
    public bool $showExpenseModal = false;
    public ?int $expense_category_id = null;
    public $expense_amount = '';
    public string $expense_date = '';
    public string $expense_description = '';

    // Record Owner Withdrawal Modal State
    public bool $showDrawalModal = false;
    public $drawal_amount = '';
    public string $drawal_date = '';
    public string $drawal_reason = 'Owner Monthly Drawdown';
    public bool $showCapitalModal = false;
    public bool $showCapitalConfirmation = false;
    public $capital_amount = '';
    public ?int $capital_financial_account_id = null;
    public string $capital_date = '';
    public string $capital_funding_source = '';
    public string $capital_reference_number = '';
    public string $capital_description = '';
    public string $capital_remarks = '';
    public $capital_proof = null;
    public string $capital_client_uuid = '';
    public ?int $reversingCapitalId = null;
    public string $capital_reversal_reason = '';
    public bool $capital_reversal_override = false;

    public bool $showCompensationModal = false;
    public ?int $compensation_user_id = null;
    public string $compensation_type = 'salary';
    public $compensation_amount = '';
    public ?string $compensation_period_start = null;
    public ?string $compensation_period_end = null;
    public string $compensation_remarks = '';

    public function mount(): void
    {
        $this->expense_date = date('Y-m-d');
        $this->drawal_date = date('Y-m-d');
        $this->capital_date = date('Y-m-d');
        $this->capital_client_uuid = (string) Str::uuid();
        $firstCategory = ExpenseCategory::where('status', 'active')->first();
        if ($firstCategory) {
            $this->expense_category_id = $firstCategory->id;
        }
        $this->compensation_user_id = User::where('status', 'active')->orderBy('name')->value('id');
        $this->compensationStatus = request()->string('compensation_status')->value();
        $this->highlightCompensationId = request()->integer('compensation') ?: null;
    }

    public function reviewCapitalInjection(): void {
        abort_unless(auth()->user()->can('create', OwnerCapitalInjection::class),403);
        $this->validate(['capital_amount'=>'required|numeric|min:0.01','capital_financial_account_id'=>'required|exists:financial_accounts,id','capital_date'=>'required|date|before_or_equal:'.now()->addYear()->toDateString(),'capital_funding_source'=>'required|string|max:120','capital_reference_number'=>'nullable|string|max:100|unique:owner_capital_injections,reference_number','capital_description'=>'nullable|string|max:1000','capital_remarks'=>'nullable|string|max:1000','capital_proof'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:5120']);
        abort_unless(FinancialAccount::whereKey($this->capital_financial_account_id)->where('is_active',true)->exists(),422);
        $this->showCapitalConfirmation=true;
    }
    public function saveCapitalInjection(): void {
        abort_unless(auth()->user()->can('create', OwnerCapitalInjection::class),403);
        $proofPath=$this->capital_proof?->store('finance-proofs/capital-injections/'.now()->format('Y/m'),'public');
        $capital=CreateCapitalInjectionAction::execute(['client_uuid'=>$this->capital_client_uuid,'amount'=>$this->capital_amount,'financial_account_id'=>$this->capital_financial_account_id,'contribution_date'=>$this->capital_date,'funding_source'=>$this->capital_funding_source,'reference_number'=>$this->capital_reference_number ?: null,'description'=>$this->capital_description ?: null,'remarks'=>$this->capital_remarks ?: null,'proof_path'=>$proofPath],auth()->user());
        $this->reset('capital_amount','capital_financial_account_id','capital_funding_source','capital_reference_number','capital_description','capital_remarks','capital_proof'); $this->capital_client_uuid=(string) Str::uuid(); $this->capital_date=date('Y-m-d'); $this->showCapitalModal=false; $this->showCapitalConfirmation=false;
        session()->flash('success','₱'.number_format($capital->amount,2).' was added to '.$capital->account->name.'. Recorded as Owner Capital Injection.');
    }
    public function reverseCapitalInjection(int $capitalId): void { $capital=OwnerCapitalInjection::findOrFail($capitalId); abort_unless(auth()->user()->can('reverse',$capital),403); $capital=ReverseCapitalInjectionAction::execute($capital,$this->capital_reversal_reason,auth()->user(),$this->capital_reversal_override); $this->reset('reversingCapitalId','capital_reversal_reason','capital_reversal_override'); session()->flash('success',"Capital Injection {$capital->capital_injection_number} was reversed successfully."); }

    public function saveExpense(): void
    {
        if (! auth()->user()->canModifyFinance()) {
            abort(403);
        }
        $this->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_amount' => 'required|numeric|min:0.01',
            'expense_description' => 'required|string|min:3|max:255',
        ]);

        try {
            $expense = RecordExpenseAction::execute([
                'expense_category_id' => $this->expense_category_id,
                'amount' => $this->expense_amount,
                'expense_date' => $this->expense_date,
                'description' => $this->expense_description,
            ]);

            session()->flash('success', "Expense {$expense->expense_number} recorded for ₱" . number_format($expense->amount, 2));
            $this->showExpenseModal = false;
            $this->expense_amount = '';
            $this->expense_description = '';
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function saveOwnerWithdrawal(): void
    {
        if (! auth()->user()->canRecordWithdrawals()) {
            abort(403);
        }
        $this->validate([
            'drawal_amount' => 'required|numeric|min:0.01',
            'drawal_reason' => 'required|string|min:3|max:255',
        ]);

        try {
            $drawal = RecordOwnerWithdrawalAction::execute([
                'amount' => $this->drawal_amount,
                'drawal_date' => $this->drawal_date,
                'reason' => $this->drawal_reason,
            ]);

            session()->flash('success', "Owner withdrawal {$drawal->drawal_number} recorded for ₱" . number_format($drawal->amount, 2));
            $this->showDrawalModal = false;
            $this->drawal_amount = '';
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function createCompensation(): void
    {
        abort_unless(auth()->user()->isOwner() || auth()->user()->isManager(), 403);
        $this->validate(['compensation_user_id'=>'required|exists:users,id','compensation_type'=>'required|in:salary,activity_incentive,quota_incentive,bonus,adjustment','compensation_amount'=>'required|numeric|min:0.01']);
        $record = CompensationRecord::create(['record_number'=>'PENDING','user_id'=>$this->compensation_user_id,'type'=>$this->compensation_type,'amount'=>$this->compensation_amount,'period_start'=>$this->compensation_period_start,'period_end'=>$this->compensation_period_end,'remarks'=>$this->compensation_remarks,'status'=>'pending_approval','created_by'=>auth()->id(),'updated_by'=>auth()->id()]);
        $record->update(['record_number'=>'CMP-'.str_pad($record->id,6,'0',STR_PAD_LEFT)]);
        ActivityLogService::log('Compensation Created',"Created {$record->type} compensation {$record->record_number} for ₱".number_format($record->amount,2).'.',$record,['compensation_type'=>$record->type,'amount'=>$record->amount,'new_status'=>'pending_approval']);
        $this->showCompensationModal=false; $this->compensation_amount=''; $this->compensation_remarks=''; session()->flash('success','Compensation submitted for Owner approval.');
    }

    public function approveCompensation(int $recordId): void
    {
        abort_unless(auth()->user()->isOwner(), 403); $record=CompensationRecord::findOrFail($recordId);
        if ($record->status !== 'pending_approval') { session()->flash('error','This compensation record is no longer awaiting approval.'); return; }
        $record->update(['status'=>'payable','approved_at'=>now(),'approved_by'=>auth()->id(),'posted_to_finance_at'=>now(),'updated_by'=>auth()->id()]);
        ActivityLogService::log('Compensation Approved',"Approved {$record->record_number} as payable.",$record,['previous_status'=>'pending_approval','new_status'=>'payable']);
        $record->load('user');
        NotificationService::notifyCompensationApproved($record);
        session()->flash('success','Compensation is now payable and reserved from available funds.');
    }

    public function payCompensation(int $recordId): void
    {
        try { $record=PayCompensationAction::execute(CompensationRecord::findOrFail($recordId),auth()->user()); session()->flash('success',"{$record->record_number} was paid and recorded in cash transactions."); } catch (Exception $e) { session()->flash('error',$e->getMessage()); }
    }

    public function render()
    {
        $canAccessFinance = auth()->user()->canAccessFinance();
        $currentCash = $canAccessFinance ? FinanceService::getCurrentCash() : null;
        $todayIncome = $canAccessFinance ? FinanceService::getTodayIncome() : null;
        $todayExpenses = $canAccessFinance ? FinanceService::getTodayExpenses() : null;
        $monthlyProfit = $canAccessFinance ? FinanceService::getMonthlyProfit() : null;
        $availableFunds = $canAccessFinance ? FinanceService::getAvailableBusinessFunds() : null;
        $capitalAddedThisMonth = $canAccessFinance ? FinanceService::getCapitalAddedThisMonth() : 0;
        $totalOwnerCapital = $canAccessFinance ? FinanceService::getTotalOwnerCapital() : 0;
        $ownerWithdrawalsThisMonth = $canAccessFinance ? FinanceService::getOwnerWithdrawalsThisMonth() : 0;

        $query = CashTransaction::with(['user', 'order', 'expense', 'ownerDrawal']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('transaction_number', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->selectedType)) {
            $query->where('type', $this->selectedType);
        }

        $cashTransactions = $query->latest('transaction_date')->paginate(10);
        $expenseCategories = ExpenseCategory::where('status', 'active')->get();
        $compensationQuery = CompensationRecord::with('user')->latest('id');
        if (in_array($this->compensationStatus, ['pending_approval', 'payable', 'paid'], true)) {
            $compensationQuery->where('status', $this->compensationStatus);
        }
        if (auth()->user()->isStaff()) $compensationQuery->where('user_id', auth()->id());
        if (auth()->user()->isManager()) $compensationQuery->whereHas('user', fn ($query) => $query->where('role', '!=', 'owner'));
        $compensationRecords = $compensationQuery->take(12)->get();
        $compensationCommitments = $canAccessFinance ? FinanceService::getCompensationCommitments() : 0;

        return view('livewire.finance.index', [
            'currentCash' => $currentCash,
            'todayIncome' => $todayIncome,
            'todayExpenses' => $todayExpenses,
            'monthlyProfit' => $monthlyProfit,
            'availableFunds' => $availableFunds,
            'capitalAddedThisMonth' => $capitalAddedThisMonth,
            'totalOwnerCapital' => $totalOwnerCapital,
            'ownerWithdrawalsThisMonth' => $ownerWithdrawalsThisMonth,
            'capitalInjections' => $canAccessFinance ? OwnerCapitalInjection::with('account')->latest('contribution_date')->take(12)->get() : collect(),
            'financialAccounts' => FinancialAccount::where('is_active', true)->orderBy('name')->get(),
            'cashTransactions' => $cashTransactions,
            'expenseCategories' => $expenseCategories,
            'canAccessFinance' => $canAccessFinance,
            'canModifyFinance' => auth()->user()->canModifyFinance(),
            'canRecordWithdrawals' => auth()->user()->canRecordWithdrawals(),
            'compensationRecords' => $compensationRecords,
            'compensationCommitments' => $compensationCommitments,
            'compensationUsers' => User::where('status','active')->orderBy('name')->get(),
            'canViewCompensation' => true,
        ])->layout('layouts.app', ['pageHeader' => 'Finance & Cash Movement']);
    }
}
