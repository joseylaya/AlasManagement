<?php

namespace App\Livewire\Dashboard;

use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\CompensationRecord;
use App\Models\Setting;
use App\Services\FinanceService;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $user = auth()->user();

        // ─── Shared (all roles) ───────────────────────────────────
        $pendingOrdersCount   = Order::where('order_status', 'pending')->count();
        $pendingApprovalCount = Order::where('approval_status', 'pending_approval')->count();
        $ordersToFulfilCount  = Order::whereIn('order_status', ['confirmed', 'preparing'])->count();
        $lowStockCount        = Inventory::whereColumn('current_stock', '<=', 'min_stock_threshold')->count();

        $lowStockItems = Inventory::with('product')
            ->whereColumn('current_stock', '<=', 'min_stock_threshold')
            ->take(6)
            ->get();

        // ─── Staff: only their own orders ────────────────────────
        $myOrdersCount = 0;
        $myRecentOrders = collect();
        $activityIncentives = 0;
        $quotaIncentives = 0;
        $quotaProgress = 0;
        $quotaTarget = 0;
        $quotaReward = 0;
        if ($user->isStaff()) {
            $myOrdersCount    = Order::where('created_by', $user->id)->whereBetween('created_at', [now()->startOfMonth(), now()])->count();
            $myRecentOrders   = Order::where('created_by', $user->id)->with('items')->latest('id')->take(5)->get();
            $pendingApprovalCount = Order::where('created_by', $user->id)->where('approval_status', 'pending_approval')->count();
            $periodStart = now()->startOfMonth();
            $activityIncentives = (float) CompensationRecord::where('user_id', $user->id)->where('type', 'activity_incentive')->where('status', '!=', 'cancelled')->whereBetween('created_at', [$periodStart, now()])->sum('amount');
            $quotaIncentives = (float) CompensationRecord::where('user_id', $user->id)->where('type', 'quota_incentive')->where('status', '!=', 'cancelled')->whereBetween('created_at', [$periodStart, now()])->sum('amount');
            $quotaTarget = (int) Setting::getByKey('staff_sales_quota_target', 15);
            $quotaReward = (float) Setting::getByKey('staff_sales_quota_reward', 500);
            $quotaProgress = Order::where('created_by', $user->id)->where('order_status', 'completed')->whereBetween('created_at', [$periodStart, now()])->count();
        }

        // ─── Finance data (Owner + Manager only) ─────────────────
        $availableFunds  = 0;
        $todayIncome     = 0;
        $todayExpenses   = 0;
        $monthlyProfit   = 0;
        $currentCash     = 0;
        $monthlyRevenue  = 0;
        $inventoryValue  = 0;

        if ($user->canAccessFinance()) {
            $availableFunds  = FinanceService::getAvailableBusinessFunds();
            $todayIncome     = FinanceService::getTodayIncome();
            $todayExpenses   = FinanceService::getTodayExpenses();
            $monthlyProfit   = FinanceService::getMonthlyProfit();
            $currentCash     = FinanceService::getCurrentCash();

            $monthlyRevenue = Order::where('order_status', 'completed')
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('total_amount');

            $inventoryValue = Inventory::join('products', 'inventories.product_id', '=', 'products.id')
                ->selectRaw('SUM(inventories.current_stock * products.cost_price) as total_val')
                ->value('total_val') ?? 0;
        }

        // ─── Activity logs (Owner + Manager see all, Staff see own) ─
        $recentLogs = ActivityLog::with('user')
            ->when($user->isStaff(), fn($q) => $q->where('user_id', $user->id))
            ->latest('id')
            ->take(8)
            ->get();

        // ─── Recent orders for list ───────────────────────────────
        $recentOrders = Order::with('items')
            ->when($user->isStaff(), fn($q) => $q->where('created_by', $user->id))
            ->latest('id')
            ->take(8)
            ->get();

        return view('livewire.dashboard.index', [
            // Role context
            'userRole'            => $user->role,
            'isOwner'             => $user->isOwner(),
            'isManager'           => $user->isManager(),
            'isStaff'             => $user->isStaff(),
            'canAccessFinance'    => $user->canAccessFinance(),
            // Shared metrics
            'pendingOrdersCount'  => $pendingOrdersCount,
            'pendingApprovalCount'=> $pendingApprovalCount,
            'ordersToFulfilCount' => $ordersToFulfilCount,
            'lowStockCount'       => $lowStockCount,
            'lowStockItems'       => $lowStockItems,
            'recentLogs'          => $recentLogs,
            'recentOrders'        => $recentOrders,
            // Finance (non-null only for owner/manager)
            'availableFunds'      => $availableFunds,
            'todayIncome'         => $todayIncome,
            'todayExpenses'       => $todayExpenses,
            'monthlyProfit'       => $monthlyProfit,
            'monthlyRevenue'      => $monthlyRevenue,
            'currentCash'         => $currentCash,
            'inventoryValue'      => $inventoryValue,
            // Staff-specific
            'myOrdersCount'       => $myOrdersCount,
            'myRecentOrders'      => $myRecentOrders,
            'activityIncentives'  => $activityIncentives,
            'quotaIncentives'     => $quotaIncentives,
            'quotaProgress'       => $quotaProgress,
            'quotaTarget'         => $quotaTarget,
            'quotaReward'         => $quotaReward,
        ])->layout('layouts.app', ['pageHeader' => 'Business Command Center']);
    }
}
