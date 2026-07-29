<?php

namespace App\Livewire\Reports;

use App\Models\Expense;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OwnerDrawal;
use App\Services\FinanceService;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $totalSalesCount = Order::where('order_status', 'completed')->count();
        $totalSalesRevenue = (float) Order::where('order_status', 'completed')->sum('total_amount');
        $totalExpensesAmount = (float) Expense::sum('amount');
        $totalOwnerDrawals = (float) OwnerDrawal::sum('amount');
        $monthlyProfit = FinanceService::getMonthlyProfit();

        $expenseByCategory = Expense::with('category')
            ->selectRaw('expense_category_id, sum(amount) as total_amount')
            ->groupBy('expense_category_id')
            ->get();

        $inventoryValuation = Inventory::with('product')->get()->sum(function ($inv) {
            return $inv->product ? ($inv->current_stock * $inv->product->cost_price) : 0;
        });

        $lowStockReport = Inventory::with('product')
            ->whereColumn('current_stock', '<=', 'min_stock_threshold')
            ->get();

        return view('livewire.reports.index', [
            'totalSalesCount' => $totalSalesCount,
            'totalSalesRevenue' => $totalSalesRevenue,
            'totalExpensesAmount' => $totalExpensesAmount,
            'totalOwnerDrawals' => $totalOwnerDrawals,
            'monthlyProfit' => $monthlyProfit,
            'expenseByCategory' => $expenseByCategory,
            'inventoryValuation' => $inventoryValuation,
            'lowStockReport' => $lowStockReport,
        ])->layout('layouts.app', ['pageHeader' => 'Business Reports & Analytics']);
    }
}
