<div>

    {{-- ═══ GREETING + PAGE TITLE ═══ --}}
    <div class="mb-6">
        <div class="text-[10px] font-bold text-white/30 uppercase tracking-[0.18em] mb-1">
            @php
                $hour = (int) date('H');
                $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
            @endphp
            {{ $greeting }}, {{ auth()->user()->name }}
        </div>
        <h1 class="text-[26px] font-black text-white tracking-tight leading-tight">Operations Overview</h1>
    </div>

    {{-- ═══ MONTHLY SALES HERO CARD ═══ --}}
    @php
        $monthlyTarget = 100000;
        $targetPct = $monthlyTarget > 0 ? min(100, round($monthlyRevenue / $monthlyTarget * 100)) : 0;
    @endphp
    <div class="bg-white rounded-2xl p-5 mb-3 card-press">
        <div class="flex items-center justify-between mb-1">
            <span class="text-[10px] font-bold text-[#888888] uppercase tracking-[0.12em]">Monthly Sales</span>
            <svg class="w-4 h-4 text-[#AAAAAA]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
        </div>
        <div class="text-[36px] font-black text-[#111111] tracking-tight leading-none mt-2 mb-4 tabular-nums">
            ₱{{ number_format($monthlyRevenue) }}
        </div>
        <div class="w-full bg-[#F0F0F0] rounded-full h-[5px] mb-2">
            <div class="bg-[#111111] h-[5px] rounded-full progress-bar" style="width: {{ $targetPct }}%"></div>
        </div>
        <div class="text-[12px] text-[#888888] font-medium">{{ $targetPct }}% of monthly target reached</div>
    </div>

    {{-- ═══ TODAY'S SALES + NET PROFIT ═══ --}}
    <div class="grid grid-cols-2 gap-3 mb-3">

        {{-- Today's Sales --}}
        <div class="bg-white rounded-2xl p-4 card-press">
            <div class="text-[10px] font-bold text-[#888888] uppercase tracking-[0.12em] mb-2">Today's Sales</div>
            <div class="text-[22px] font-black text-[#111111] tracking-tight leading-none tabular-nums mb-1.5">
                ₱{{ number_format($todayIncome) }}
            </div>
            <div class="flex items-center gap-1 text-[11px] font-bold {{ $todayIncome > 0 ? 'text-emerald-600' : 'text-[#AAAAAA]' }}">
                @if($todayIncome > 0)
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path d="M7 17l9.2-9.2M17 17V7H7"/>
                    </svg>
                    12.5%
                @else
                    No sales today
                @endif
            </div>
        </div>

        {{-- Net Profit --}}
        <div class="bg-white rounded-2xl p-4 card-press">
            <div class="text-[10px] font-bold text-[#888888] uppercase tracking-[0.12em] mb-2">Net Profit</div>
            <div class="text-[22px] font-black {{ $monthlyProfit >= 0 ? 'text-[#111111]' : 'text-red-600' }} tracking-tight leading-none tabular-nums mb-1.5">
                ₱{{ number_format(abs($monthlyProfit)) }}
            </div>
            @if($monthlyRevenue > 0)
                <div class="text-[11px] font-semibold text-[#888888]">
                    Margin: {{ round($monthlyProfit / max($monthlyRevenue,1) * 100) }}%
                </div>
            @endif
        </div>
    </div>

    {{-- ═══ STATS ROW ═══ --}}
    <div class="grid grid-cols-3 gap-3 mb-5">

        <div class="bg-white/10 rounded-2xl p-3.5 text-center">
            <div class="text-[20px] font-black text-white tabular-nums">{{ $pendingOrdersCount }}</div>
            <div class="text-[10px] font-semibold text-white/40 mt-0.5 uppercase tracking-wide">Pending</div>
        </div>

        <div class="bg-white/10 rounded-2xl p-3.5 text-center">
            <div class="text-[20px] font-black {{ $lowStockCount > 0 ? 'text-red-400' : 'text-white' }} tabular-nums">{{ $lowStockCount }}</div>
            <div class="text-[10px] font-semibold text-white/40 mt-0.5 uppercase tracking-wide">Low Stock</div>
        </div>

        <div class="bg-white/10 rounded-2xl p-3.5 text-center">
            <div class="text-[20px] font-black text-white tabular-nums">{{ $ordersToFulfilCount }}</div>
            <div class="text-[10px] font-semibold text-white/40 mt-0.5 uppercase tracking-wide">To Ship</div>
        </div>

    </div>

    {{-- ═══ QUICK ACTIONS ═══ --}}
    <div class="mb-5">
        <div class="text-[10px] font-bold text-white/30 uppercase tracking-[0.18em] mb-3">Quick Actions</div>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('orders.create') }}"
               class="flex items-center justify-center gap-2 bg-white text-[#111111] rounded-2xl py-4 font-bold text-[13px] transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                New Order
            </a>
            <a href="{{ route('products.create') }}"
               class="flex items-center justify-center gap-2 bg-white/10 text-white border border-white/10 rounded-2xl py-4 font-bold text-[13px] transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Add Product
            </a>
        </div>
    </div>

    {{-- ═══ CRITICAL ALERTS ═══ --}}
    <div class="mb-5">
        <div class="flex items-center justify-between mb-3">
            <div class="text-[10px] font-bold text-white/30 uppercase tracking-[0.18em]">Critical Alerts</div>
            @php $totalAlerts = $lowStockItems->count() + ($pendingOrdersCount > 0 ? 1 : 0); @endphp
            @if($totalAlerts > 0)
                <span class="text-[10px] font-bold text-white/30">{{ $totalAlerts }} Action Items</span>
            @endif
        </div>

        <div class="space-y-2">

            {{-- Low Stock Alerts --}}
            @forelse($lowStockItems as $inv)
            <a href="{{ route('inventory.index') }}"
               class="bg-white rounded-2xl p-4 flex items-center gap-3 card-press block">
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-[13px] font-bold text-[#111111] truncate">
                        Low Stock: {{ $inv->product->product_name }}
                        @if($inv->product->size) ({{ $inv->product->size }}) @endif
                    </div>
                    <div class="text-[11px] text-[#888888] mt-0.5">
                        Only {{ $inv->current_stock }} units remaining in stock
                    </div>
                </div>
                <svg class="w-4 h-4 text-[#CCCCCC] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </a>
            @empty
                {{-- No low stock: show green check --}}
            @endforelse

            {{-- Pending Orders Alert --}}
            @if($pendingOrdersCount > 0)
            <a href="{{ route('orders.index') }}"
               class="bg-white rounded-2xl p-4 flex items-center gap-3 card-press block">
                <div class="w-10 h-10 rounded-xl bg-[#F5F5F5] flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#555555]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-[13px] font-bold text-[#111111]">{{ $pendingOrdersCount }} Pending Orders</div>
                    <div class="text-[11px] text-[#888888] mt-0.5">Require confirmation or fulfillment</div>
                </div>
                <svg class="w-4 h-4 text-[#CCCCCC] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </a>
            @endif

            {{-- All clear --}}
            @if($lowStockItems->count() === 0 && $pendingOrdersCount === 0)
            <div class="bg-white rounded-2xl p-5 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[13px] font-bold text-[#111111]">All systems operational</div>
                    <div class="text-[11px] text-[#888888]">No critical alerts at this time</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ═══ RECENT ORDERS ═══ --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <div class="text-[10px] font-bold text-white/30 uppercase tracking-[0.18em]">Recent Orders</div>
            <a href="{{ route('orders.index') }}" class="text-[11px] font-bold text-white/30 hover:text-white/60 transition-colors">View All →</a>
        </div>

        <div class="space-y-2">
            @forelse($recentLogs->take(5) as $log)
            @php
                // Map activity logs to order context when possible
            @endphp
            @empty
            @endforelse

            {{-- Use recentOrders if available, else fallback --}}
            @php
                $displayOrders = \App\Models\Order::with('items.product')
                    ->latest()->take(4)->get();
            @endphp
            @forelse($displayOrders as $order)
            @php
                $statusMap = [
                    'pending'    => ['dot' => 'bg-orange-500', 'text' => 'text-orange-500',  'label' => 'Pending'],
                    'confirmed'  => ['dot' => 'bg-blue-500',   'text' => 'text-blue-500',    'label' => 'Confirmed'],
                    'processing' => ['dot' => 'bg-blue-500',   'text' => 'text-blue-500',    'label' => 'Processing'],
                    'packed'     => ['dot' => 'bg-violet-500', 'text' => 'text-violet-500',  'label' => 'Packed'],
                    'shipped'    => ['dot' => 'bg-gray-400',   'text' => 'text-gray-500',    'label' => 'Shipped'],
                    'completed'  => ['dot' => 'bg-emerald-500','text' => 'text-emerald-600', 'label' => 'Completed'],
                    'cancelled'  => ['dot' => 'bg-red-500',    'text' => 'text-red-500',     'label' => 'Cancelled'],
                ];
                $s = $statusMap[$order->order_status] ?? ['dot' => 'bg-gray-400', 'text' => 'text-gray-500', 'label' => ucfirst($order->order_status)];
            @endphp
            <a href="{{ route('orders.show', $order->id) }}"
               class="bg-white rounded-2xl p-4 block card-press">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div>
                        <span class="text-[10px] font-bold text-[#AAAAAA]">#{{ $order->order_number }}</span>
                        <div class="text-[15px] font-black text-[#111111] leading-tight mt-0.5">{{ $order->customer_name }}</div>
                    </div>
                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        <span class="flex items-center gap-1 text-[10px] font-bold {{ $s['text'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $s['dot'] }} inline-block"></span>
                            {{ $s['label'] }}
                        </span>
                        <div class="text-[15px] font-black text-[#111111] tabular-nums">₱{{ number_format($order->total_amount) }}</div>
                    </div>
                </div>
                @if($order->items->count() > 0)
                <div class="text-[11px] text-[#888888]">
                    {{ $order->items->first()->product->product_name ?? '—' }}
                    @if($order->items->count() > 1)
                        + {{ $order->items->count() - 1 }} more
                    @endif
                </div>
                @endif
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-[#F5F5F5]">
                    <span class="text-[10px] text-[#AAAAAA]">{{ ucfirst($order->delivery_method ?? 'N/A') }} · {{ $order->created_at->diffForHumans() }}</span>
                    @if(in_array($order->order_status, ['pending']))
                    <span class="text-[11px] font-bold text-[#111111] bg-[#F5F5F5] px-3 py-1 rounded-lg">Confirm →</span>
                    @elseif(in_array($order->order_status, ['confirmed','processing']))
                    <span class="text-[11px] font-bold text-[#111111] bg-[#F5F5F5] px-3 py-1 rounded-lg">Process →</span>
                    @endif
                </div>
            </a>
            @empty
            <div class="bg-white rounded-2xl p-8 text-center">
                <div class="text-[13px] font-semibold text-[#888888]">No orders yet</div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ═══ FLOATING ACTION BUTTON (FAB) ═══ --}}
    <a href="{{ route('orders.create') }}"
       class="fixed bottom-20 right-4 z-20 w-14 h-14 bg-white rounded-full flex items-center justify-center shadow-2xl shadow-black/40 transition-all active:scale-90">
        <svg class="w-6 h-6 text-[#111111]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M12 5v14M5 12h14"/>
        </svg>
    </a>

</div>
