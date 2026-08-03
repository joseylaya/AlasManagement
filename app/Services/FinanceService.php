<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OwnerDrawal;
use App\Models\CompensationRecord;
use App\Models\OwnerCapitalInjection;
use Carbon\Carbon;

class FinanceService
{
    public static function getCurrentCash(): float
    {
        return (float) CashTransaction::sum('amount');
    }

    public static function getTodayIncome(): float
    {
        return (float) CashTransaction::where('amount', '>', 0)
            ->whereDate('transaction_date', Carbon::today())
            ->sum('amount');
    }

    public static function getTodayExpenses(): float
    {
        return (float) abs(CashTransaction::where('type', 'expense')
            ->whereDate('transaction_date', Carbon::today())
            ->sum('amount'));
    }

    public static function getMonthlyProfit(): float
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // 1. Total Completed Sales Revenue in Month
        $revenue = (float) Order::where('order_status', 'completed')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        // 2. Cost of Goods Sold (COGS) in Month
        $completedOrderIds = Order::where('order_status', 'completed')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->pluck('id');

        $cogs = 0.00;
        $orderItems = OrderItem::with('product')->whereIn('order_id', $completedOrderIds)->get();
        foreach ($orderItems as $item) {
            $costPrice = $item->product ? $item->product->cost_price : 0;
            $cogs += ($costPrice * $item->quantity);
        }

        // 3. Operating Expenses in Month
        $expenses = (float) Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Profit = Revenue - COGS - Expenses (Owner Withdrawals do NOT reduce profit)
        return $revenue - $cogs - $expenses;
    }

    public static function getAvailableBusinessFunds(): float
    {
        $currentCash = static::getCurrentCash();
        
        // Pending expenses or obligations (if any)
        $pendingExpenses = (float) Expense::where('status', 'pending')->sum('amount');

        return max(0.00, $currentCash - $pendingExpenses - static::getCompensationCommitments());
    }

    public static function getCompensationCommitments(): float
    {
        return (float) CompensationRecord::whereIn('status', ['approved', 'payable'])->sum('amount');
    }

    public static function getCapitalAddedThisMonth(): float { return (float) OwnerCapitalInjection::where('status','posted')->whereBetween('contribution_date',[now()->startOfMonth(),now()->endOfMonth()])->sum('amount'); }
    public static function getTotalOwnerCapital(): float { return (float) OwnerCapitalInjection::where('status','posted')->sum('amount'); }
    public static function getOwnerWithdrawalsThisMonth(): float { return (float) abs(CashTransaction::where('type','owner_withdrawal')->whereBetween('transaction_date',[now()->startOfMonth(),now()->endOfMonth()])->sum('amount')); }
}
