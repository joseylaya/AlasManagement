<div>

    {{-- ═══════════════════════════════════════════════════════
         DESKTOP VIEW  (lg+): original PC layout
         ═══════════════════════════════════════════════════════ --}}

    {{-- ===== PAGE HEADER ===== --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-[#111111] leading-tight">Business Command Center</h2>
            <p class="text-[13px] text-[#888888] mt-0.5">Real-time overview of your fashion ecosystem operations.</p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <button class="flex items-center gap-1.5 px-3.5 py-2 text-[12px] font-semibold text-[#444444] bg-white border border-[#E0E0E0] rounded-lg hover:bg-[#F5F5F5] transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Last 24 Hours
            </button>
            <button class="flex items-center gap-1.5 px-3.5 py-2 text-[12px] font-semibold text-white bg-[#111111] hover:bg-[#333333] rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export Report
            </button>
        </div>
    </div>

    {{-- ===== KPI ROW 1: Main Financial Metrics ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        {{-- Today's Sales --}}
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 bg-[#F5F5F5] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#666666]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
                <span class="text-[11px] font-bold text-emerald-600">+{{ number_format($todayIncome > 0 ? 12.5 : 0, 1) }}%</span>
            </div>
            <div class="text-[18px] font-bold text-[#111111] tabular-nums">₱{{ number_format($todayIncome, 2) }}</div>
            <div class="text-[12px] text-[#888888] mt-0.5">Today's Sales</div>
        </div>

        {{-- Monthly Sales --}}
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 bg-[#F5F5F5] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#666666]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <span class="text-[11px] font-bold text-emerald-600">+8.2%</span>
            </div>
            <div class="text-[18px] font-bold text-[#111111] tabular-nums">₱{{ number_format($monthlyRevenue, 2) }}</div>
            <div class="text-[12px] text-[#888888] mt-0.5">Monthly Sales</div>
        </div>

        {{-- Net Profit --}}
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 bg-[#F5F5F5] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#666666]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
                <span class="text-[11px] font-bold {{ $monthlyProfit >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                    {{ $monthlyProfit >= 0 ? '+' : '-' }}{{ number_format(abs($monthlyProfit / max($monthlyRevenue, 1) * 100), 1) }}%
                </span>
            </div>
            <div class="text-[18px] font-bold {{ $monthlyProfit >= 0 ? 'text-[#111111]' : 'text-red-600' }} tabular-nums">₱{{ number_format(abs($monthlyProfit), 2) }}</div>
            <div class="text-[12px] text-[#888888] mt-0.5">Net Profit (MTD)</div>
        </div>

        {{-- Cash Available --}}
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 bg-[#F5F5F5] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#666666]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-[18px] font-bold text-[#111111] tabular-nums">₱{{ number_format($currentCash, 2) }}</div>
            <div class="text-[12px] text-[#888888] mt-0.5">Cash Available</div>
        </div>
    </div>

    {{-- ===== KPI ROW 2: Operational Metrics ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        {{-- Inventory Value --}}
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="text-[18px] font-bold text-[#111111] tabular-nums mb-1">₱{{ number_format($inventoryValue, 0) }}</div>
            <div class="text-[12px] text-[#888888] mb-3">Inventory Value</div>
            <div class="w-full bg-[#F0F0F0] rounded-full h-1.5">
                <div class="bg-[#111111] h-1.5 rounded-full" style="width: 75%"></div>
            </div>
            <div class="text-[10px] text-[#AAAAAA] mt-1.5">75% Warehouse capacity used</div>
        </div>

        {{-- Pending Orders --}}
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="text-[18px] font-bold text-[#111111] tabular-nums mb-1">{{ $pendingOrdersCount }}</div>
            <div class="text-[12px] text-[#888888] mb-2">Pending Orders</div>
            @if($pendingOrdersCount > 0)
                <div class="flex items-center gap-1.5 text-[11px] font-semibold text-red-500">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    {{ $pendingOrdersCount }} need attention
                </div>
            @else
                <div class="text-[11px] text-emerald-600 font-semibold">All orders processed</div>
            @endif
        </div>

        {{-- Orders to Fulfil --}}
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="text-[18px] font-bold text-[#111111] tabular-nums mb-1">{{ $ordersToFulfilCount }}</div>
            <div class="text-[12px] text-[#888888] mb-2">Orders to Fulfil</div>
            <div class="flex items-center gap-1.5 text-[11px] text-[#888888]">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Ready to process
            </div>
        </div>

        {{-- Low Stock Items --}}
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="text-[18px] font-bold {{ $lowStockCount > 0 ? 'text-red-600' : 'text-[#111111]' }} tabular-nums mb-1">{{ $lowStockCount }}</div>
            <div class="text-[12px] text-[#888888] mb-2">Low Stock Items</div>
            @if($lowStockCount > 0)
                <a href="{{ route('inventory.index') }}" class="text-[11px] font-semibold text-red-500 hover:text-red-700 transition-colors">
                    View inventory →
                </a>
            @else
                <div class="text-[11px] text-emerald-600 font-semibold">Stock levels healthy</div>
            @endif
        </div>
    </div>

    {{-- ===== 2-COLUMN: Critical Alerts + Quick Actions | Recent Activity ===== --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

        {{-- LEFT: Critical Alerts + Quick Actions --}}
        <div class="xl:col-span-2 space-y-4">

            {{-- Critical Alerts --}}
            <div class="bg-white rounded-xl border border-[#E8E8E8]">
                <div class="flex items-center justify-between px-5 py-4 border-b border-[#F0F0F0]">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <span class="text-[13px] font-bold text-[#111111]">Critical Alerts</span>
                    </div>
                    @if($lowStockItems->count() > 0)
                        <span class="text-[11px] font-bold text-white bg-red-500 px-2 py-0.5 rounded-full">{{ $lowStockItems->count() }}</span>
                    @endif
                </div>

                <ul class="divide-y divide-[#F5F5F5]">
                    @forelse($lowStockItems as $inv)
                        <li class="flex items-start gap-3 px-5 py-3.5">
                            <div class="w-1.5 h-1.5 rounded-full bg-red-500 mt-1.5 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <div class="text-[12px] font-semibold text-[#111111]">
                                    Low Stock: {{ $inv->product->product_name }}
                                    @if($inv->product->size) ({{ $inv->product->size }}) @endif
                                </div>
                                <div class="text-[11px] text-[#888888] mt-0.5">
                                    {{ $inv->current_stock }} units remaining. Min threshold: {{ $inv->min_stock_threshold }}.
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center">
                            <div class="text-[12px] text-[#888888]">No critical alerts at this time.</div>
                        </li>
                    @endforelse
                </ul>

                @if($lowStockItems->count() > 0)
                    <div class="px-5 py-3 border-t border-[#F0F0F0]">
                        <a href="{{ route('inventory.index') }}"
                           class="w-full flex items-center justify-center py-2 text-[12px] font-semibold text-[#555555] bg-white border border-[#E0E0E0] rounded-lg hover:bg-[#F5F5F5] transition-colors">
                            Resolve All Alerts
                        </a>
                    </div>
                @endif
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white rounded-xl border border-[#E8E8E8]">
                <div class="px-5 py-4 border-b border-[#F0F0F0]">
                    <span class="text-[13px] font-bold text-[#111111]">Quick Actions</span>
                </div>
                <div class="p-3 space-y-1">
                    <a href="{{ route('orders.create') }}"
                       class="flex items-center justify-between px-3 py-3 rounded-lg hover:bg-[#F5F5F5] transition-colors group">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 bg-[#F0F0F0] rounded-lg flex items-center justify-center group-hover:bg-[#E8E8E8] transition-colors">
                                <svg class="w-3.5 h-3.5 text-[#555555]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            </div>
                            <span class="text-[13px] font-semibold text-[#333333]">Create New Order</span>
                        </div>
                        <svg class="w-3.5 h-3.5 text-[#BBBBBB]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                    <a href="{{ route('products.create') }}"
                       class="flex items-center justify-between px-3 py-3 rounded-lg hover:bg-[#F5F5F5] transition-colors group">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 bg-[#F0F0F0] rounded-lg flex items-center justify-center group-hover:bg-[#E8E8E8] transition-colors">
                                <svg class="w-3.5 h-3.5 text-[#555555]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                            </div>
                            <span class="text-[13px] font-semibold text-[#333333]">Add Product</span>
                        </div>
                        <svg class="w-3.5 h-3.5 text-[#BBBBBB]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                    @if(auth()->user()->canAccessFinance())
                    <a href="{{ route('finance.index') }}"
                       class="flex items-center justify-between px-3 py-3 rounded-lg hover:bg-[#F5F5F5] transition-colors group">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 bg-[#F0F0F0] rounded-lg flex items-center justify-center group-hover:bg-[#E8E8E8] transition-colors">
                                <svg class="w-3.5 h-3.5 text-[#555555]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <span class="text-[13px] font-semibold text-[#333333]">Log Expense</span>
                        </div>
                        <svg class="w-3.5 h-3.5 text-[#BBBBBB]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT: Recent Activity --}}
        <div class="xl:col-span-3">
            <div class="bg-white rounded-xl border border-[#E8E8E8] h-full">
                <div class="flex items-center justify-between px-5 py-4 border-b border-[#F0F0F0]">
                    <span class="text-[13px] font-bold text-[#111111]">Recent Activity</span>
                    <a href="{{ route('activity-logs.index') }}" class="text-[12px] font-semibold text-[#888888] hover:text-[#111111] transition-colors">
                        View All History
                    </a>
                </div>

                <ul class="divide-y divide-[#F5F5F5]">
                    @forelse($recentLogs as $log)
                        <li class="flex items-start gap-4 px-5 py-4">
                            <div class="w-8 h-8 rounded-full bg-[#F0F0F0] flex items-center justify-center flex-shrink-0 mt-0.5">
                                <span class="text-[11px] font-bold text-[#666666]">
                                    {{ $log->user ? strtoupper(substr($log->user->name, 0, 1)) : 'S' }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-[12px] font-bold text-[#111111]">
                                        {{ $log->user ? $log->user->name : 'System' }}
                                    </span>
                                    <span class="text-[10px] text-[#AAAAAA] flex-shrink-0">{{ $log->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-[12px] text-[#555555] mt-0.5 font-semibold">{{ $log->action }}</div>
                                <div class="text-[11px] text-[#888888] mt-0.5 line-clamp-2">{{ Str::limit($log->description, 80) }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-12 text-center text-[#888888] text-[13px]">No activity recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

</div>
