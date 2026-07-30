<?php

namespace App\Livewire\Finance;

use App\Actions\RecordExpenseAction;
use App\Actions\RecordOwnerWithdrawalAction;
use App\Models\CashTransaction;
use App\Models\ExpenseCategory;

use App\Services\FinanceService;
use Exception;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedType = '';

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

    public function mount(): void
    {
        $this->expense_date = date('Y-m-d');
        $this->drawal_date = date('Y-m-d');
        $firstCategory = ExpenseCategory::where('status', 'active')->first();
        if ($firstCategory) {
            $this->expense_category_id = $firstCategory->id;
        }
    }

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

    public function render()
    {
        $canAccessFinance = auth()->user()->canAccessFinance();
        $currentCash = $canAccessFinance ? FinanceService::getCurrentCash() : null;
        $todayIncome = $canAccessFinance ? FinanceService::getTodayIncome() : null;
        $todayExpenses = $canAccessFinance ? FinanceService::getTodayExpenses() : null;
        $monthlyProfit = $canAccessFinance ? FinanceService::getMonthlyProfit() : null;
        $availableFunds = $canAccessFinance ? FinanceService::getAvailableBusinessFunds() : null;

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

        return view('livewire.finance.index', [
            'currentCash' => $currentCash,
            'todayIncome' => $todayIncome,
            'todayExpenses' => $todayExpenses,
            'monthlyProfit' => $monthlyProfit,
            'availableFunds' => $availableFunds,
            'cashTransactions' => $cashTransactions,
            'expenseCategories' => $expenseCategories,
            'canAccessFinance' => $canAccessFinance,
            'canModifyFinance' => auth()->user()->canModifyFinance(),
            'canRecordWithdrawals' => auth()->user()->canRecordWithdrawals(),
        ])->layout('layouts.app', ['pageHeader' => 'Finance & Cash Movement']);
    }
}
