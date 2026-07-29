<?php

namespace App\Livewire\Dashboard;

use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\Order;

use App\Services\FinanceService;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $availableFunds = FinanceService::getAvailableBusinessFunds();
        $todayIncome    = FinanceService::getTodayIncome();
        $todayExpenses  = FinanceService::getTodayExpenses();
        $monthlyProfit  = FinanceService::getMonthlyProfit();
        $currentCash    = FinanceService::getCurrentCash();

        $monthlyRevenue = Order::where('order_status', 'completed')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total_amount');

        $inventoryValue = Inventory::join('products', 'inventories.product_id', '=', 'products.id')
            ->selectRaw('SUM(inventories.current_stock * products.cost_price) as total_val')
            ->value('total_val') ?? 0;

        $pendingOrdersCount = Order::where('order_status', 'pending')->count();
        $ordersToFulfilCount = Order::whereIn('order_status', ['confirmed', 'processing'])->count();
        $lowStockCount = Inventory::whereColumn('current_stock', '<=', 'min_stock_threshold')->count();

        $lowStockItems = Inventory::with('product')
            ->whereColumn('current_stock', '<=', 'min_stock_threshold')
            ->take(6)
            ->get();

        $recentLogs = ActivityLog::with('user')
            ->latest('id')
            ->take(8)
            ->get();

        $recentOrders = Order::latest('id')->take(8)->get();

        return view('livewire.dashboard.index', [
            'availableFunds'     => $availableFunds,
            'todayIncome'        => $todayIncome,
            'todayExpenses'      => $todayExpenses,
            'monthlyProfit'      => $monthlyProfit,
            'monthlyRevenue'     => $monthlyRevenue,
            'currentCash'        => $currentCash,
            'inventoryValue'     => $inventoryValue,
            'pendingOrdersCount' => $pendingOrdersCount,
            'ordersToFulfilCount'=> $ordersToFulfilCount,
            'lowStockCount'      => $lowStockCount,
            'lowStockItems'      => $lowStockItems,
            'recentLogs'         => $recentLogs,
            'recentOrders'       => $recentOrders,
        ])->layout('layouts.app', ['pageHeader' => 'Business Command Center']);
    }
}
