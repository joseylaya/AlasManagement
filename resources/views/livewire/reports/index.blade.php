<div class="space-y-6">

    <!-- Overview Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Total Sales Revenue</span>
            <span class="text-3xl font-black text-slate-900 tracking-tight mt-2 block">₱{{ number_format($totalSalesRevenue, 2) }}</span>
            <p class="text-xs text-slate-500 mt-1">From {{ $totalSalesCount }} completed orders</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Total Operating Expenses</span>
            <span class="text-3xl font-black text-rose-600 tracking-tight mt-2 block">₱{{ number_format($totalExpensesAmount, 2) }}</span>
            <p class="text-xs text-slate-500 mt-1">Operational & marketing costs</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Inventory Asset Valuation</span>
            <span class="text-3xl font-black text-slate-900 tracking-tight mt-2 block">₱{{ number_format($inventoryValuation, 2) }}</span>
            <p class="text-xs text-slate-500 mt-1">Total cost value of in-stock items</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Owner Drawdowns</span>
            <span class="text-3xl font-black text-purple-600 tracking-tight mt-2 block">₱{{ number_format($totalOwnerDrawals, 2) }}</span>
            <p class="text-xs text-slate-500 mt-1">Total owner cash withdrawals</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Expense Category Breakdown Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-900">Expenses Breakdown by Category</h2>
            <div class="space-y-3">
                @forelse($expenseByCategory as $item)
                    @php
                        $percentage = $totalExpensesAmount > 0 ? ($item->total_amount / $totalExpensesAmount) * 100 : 0;
                    @endphp
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs font-bold">
                            <span class="text-slate-800">{{ $item->category->name ?? 'Uncategorized' }}</span>
                            <span class="text-slate-900">₱{{ number_format($item->total_amount, 2) }} ({{ number_format($percentage, 1) }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                            <div class="bg-rose-500 h-full rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-xs text-slate-500 text-center py-6">No category expense records found.</div>
                @endforelse
            </div>
        </div>

        <!-- Low Stock Summary Report -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900">Low Stock Reorder Report</h2>
                <span class="text-xs font-bold bg-rose-100 text-rose-800 px-2.5 py-0.5 rounded-full">{{ count($lowStockReport) }} items</span>
            </div>

            <div class="space-y-3">
                @forelse($lowStockReport as $inv)
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-slate-900">{{ $inv->product->product_name ?? 'N/A' }}</div>
                            <div class="text-[11px] text-slate-500 font-mono">{{ $inv->product->sku ?? '' }}</div>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-rose-600 block">{{ $inv->current_stock }} remaining</span>
                            <span class="text-[10px] text-slate-400">Reorder threshold: {{ $inv->min_stock_threshold }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-xs text-slate-500 text-center py-6">No low stock alerts currently active.</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
