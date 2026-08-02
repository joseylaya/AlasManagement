<div class="space-y-6">

    {{-- ===================================================================
         STAFF DASHBOARD
         =================================================================== --}}
    @if($isStaff)

    {{-- Greeting --}}
    <div>
        <p class="text-[12px] text-[#AAAAAA] uppercase tracking-wider font-semibold mb-1">
            @php $hour = (int) date('H'); echo $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening'); @endphp,
            {{ auth()->user()->name }}
        </p>
        <h2 class="text-[22px] font-bold text-[#111111]">My Orders Overview</h2>
        <p class="text-[13px] text-[#888888] mt-0.5">Build your monthly sales progress and incentives.</p>
    </div>

    {{-- Incentive motivation --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="flex items-start justify-between gap-3"><div><div class="text-[11px] font-bold text-[#888888] uppercase tracking-wide">Activity Incentives</div><div class="mt-1 text-[22px] font-black text-emerald-700 tabular-nums">₱{{ number_format($activityIncentives, 2) }}</div><p class="mt-1 text-[12px] text-[#777777]">Earned this month</p></div><div x-data="{ open:false }" class="relative"><button type="button" @click="open=!open" :aria-expanded="open.toString()" class="flex h-8 w-8 items-center justify-center rounded-full border border-[#D8D8D8] text-[13px] font-black text-[#555555]" aria-label="About activity incentives">i</button><div x-show="open" @click.outside="open=false" x-cloak class="absolute right-0 top-10 z-20 w-64 rounded-xl border border-[#E2E2E2] bg-white p-3 text-[12px] leading-relaxed text-[#555555] shadow-lg">An activity incentive appears here only after the Owner has approved it for payment.</div></div></div>
        </div>
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="flex items-start justify-between gap-3"><div class="flex-1"><div class="text-[11px] font-bold text-[#888888] uppercase tracking-wide">Sales Quota Incentive</div><div class="mt-1 text-[22px] font-black text-[#111111] tabular-nums">{{ min($quotaProgress, $quotaTarget) }} <span class="text-[14px] font-semibold text-[#888888]">/ {{ $quotaTarget }}</span></div><p class="mt-1 text-[12px] text-[#777777]">Complete {{ $quotaTarget }} sales to qualify for ₱{{ number_format($quotaReward, 0) }}.</p><div class="mt-3 h-2 overflow-hidden rounded-full bg-[#F0F0F0]"><div class="h-full rounded-full bg-[#111111]" style="width: {{ $quotaTarget > 0 ? min(100, ($quotaProgress / $quotaTarget) * 100) : 0 }}%"></div></div></div><div x-data="{ open:false }" class="relative"><button type="button" @click="open=!open" :aria-expanded="open.toString()" class="flex h-8 w-8 items-center justify-center rounded-full border border-[#D8D8D8] text-[13px] font-black text-[#555555]" aria-label="About sales quota incentives">i</button><div x-show="open" @click.outside="open=false" x-cloak class="absolute right-0 top-10 z-20 w-64 rounded-xl border border-[#E2E2E2] bg-white p-3 text-[12px] leading-relaxed text-[#555555] shadow-lg">Only completed, valid sales you created count toward the monthly quota. Qualification and payout follow the standard review process.</div></div></div>
            @if($quotaIncentives > 0)<p class="mt-3 text-[11px] font-semibold text-emerald-700">₱{{ number_format($quotaIncentives, 2) }} quota incentive recorded this month.</p>@endif
        </div>
    </div>

    {{-- Staff KPIs --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5"><div class="text-[11px] font-bold text-[#888888] uppercase tracking-wide">My Orders This Month</div><div class="mt-1 text-[24px] font-black text-[#111111] tabular-nums">{{ $myOrdersCount }}</div><p class="mt-1 text-[12px] text-[#777777]">Your recorded orders for the current month.</p><details class="mt-2 text-[11px] text-[#666666]"><summary class="cursor-pointer font-semibold">ⓘ What counts?</summary><p class="mt-1">Every order you create this month is included, whether it is still waiting for approval or already complete.</p></details></div>
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5"><div class="text-[22px] font-black {{ $lowStockCount > 0 ? 'text-red-600' : 'text-[#111111]' }} tabular-nums mb-1">{{ $lowStockCount }}</div><div class="text-[12px] text-[#888888]">Low Stock Items</div><details class="mt-2 text-[11px] text-[#666666]"><summary class="cursor-pointer font-semibold">ⓘ What does this mean?</summary><p class="mt-1">These products need restocking soon. Let the team know if an item is needed for a customer order.</p></details></div>
        <div class="col-span-2 sm:col-span-1 bg-white rounded-xl border border-[#E8E8E8] p-5"><div class="text-[22px] font-black {{ $pendingApprovalCount > 0 ? 'text-orange-600' : 'text-[#111111]' }} tabular-nums mb-1">{{ $pendingApprovalCount }}</div><div class="text-[12px] text-[#888888]">Awaiting Approval</div>@if($pendingApprovalCount > 0)<div class="text-[11px] text-orange-600 font-semibold mt-1">Submitted for review</div>@endif<details class="mt-2 text-[11px] text-[#666666]"><summary class="cursor-pointer font-semibold">ⓘ What happens next?</summary><p class="mt-1">Orders move to fulfillment after the standard team review.</p></details></div>
    </div>

    {{-- Quick Action --}}
    <div>
        <div class="text-[11px] font-bold text-[#AAAAAA] uppercase tracking-wider mb-3">Quick Actions</div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('promotion-activities.index') }}" wire:navigate
               class="inline-flex items-center gap-2 px-5 py-3 bg-white border border-[#E0E0E0] text-[#333333] rounded-xl font-semibold text-[13px] hover:bg-[#F5F5F5] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4Z"/></svg>
                Submit Activity
            </a>
            <a href="{{ route('orders.create') }}" wire:navigate
               class="inline-flex items-center gap-2 px-5 py-3 bg-[#111111] text-white rounded-xl font-semibold text-[13px] hover:bg-[#333333] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Create New Order
            </a>
            <a href="{{ route('orders.index') }}" wire:navigate
               class="inline-flex items-center gap-2 px-5 py-3 bg-white border border-[#E0E0E0] text-[#333333] rounded-xl font-semibold text-[13px] hover:bg-[#F5F5F5] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                View My Orders
            </a>
        </div>
    </div>

    {{-- My Recent Orders --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <div class="text-[13px] font-bold text-[#111111]">My Recent Orders</div>
            <a href="{{ route('orders.index') }}" wire:navigate class="text-[12px] font-semibold text-[#888888] hover:text-[#111111]">View All</a>
        </div>
        <div class="space-y-2">
            @forelse($myRecentOrders as $order)
            @php
                $approvalColors = [
                    'pending_approval' => ['badge' => 'bg-orange-50 text-orange-700', 'label' => 'Pending Approval'],
                    'approved'         => ['badge' => 'bg-emerald-50 text-emerald-700','label' => 'Approved'],
                    'rejected'         => ['badge' => 'bg-red-50 text-red-700',        'label' => 'Rejected'],
                ];
                $ac = $approvalColors[$order->approval_status] ?? ['badge'=>'bg-gray-50 text-gray-600','label'=>ucfirst($order->approval_status)];
            @endphp
            <a href="{{ route('orders.show', $order->id) }}" wire:navigate
               class="block bg-white rounded-xl border border-[#E8E8E8] px-4 py-3.5 hover:border-[#CCCCCC] transition-colors">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-[12px] font-mono font-bold text-[#AAAAAA]">{{ $order->order_number }}</span>
                            <span class="text-[11px] font-semibold {{ $ac['badge'] }} px-2 py-0.5 rounded-full">{{ $ac['label'] }}</span>
                        </div>
                        <div class="text-[14px] font-semibold text-[#111111] mt-0.5">{{ $order->customer_name }}</div>
                    </div>
                    <div class="text-[14px] font-black text-[#111111] tabular-nums flex-shrink-0">₱{{ number_format($order->total_amount, 0) }}</div>
                </div>
                @if($order->approval_status === 'rejected' && $order->rejection_reason)
                <div class="mt-2 text-[11px] text-red-500 bg-red-50 px-3 py-1.5 rounded-lg">
                    Rejected: {{ $order->rejection_reason }}
                </div>
                @endif
                <div class="text-[11px] text-[#AAAAAA] mt-1.5">{{ $order->created_at->diffForHumans() }}</div>
            </a>
            @empty
            <div class="bg-white rounded-xl border border-[#E8E8E8] px-4 py-10 text-center">
                <div class="text-[13px] text-[#AAAAAA]">No orders yet. Create your first order above.</div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ===================================================================
         MANAGER DASHBOARD
         =================================================================== --}}
    @elseif($isManager)

    {{-- Greeting --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-[22px] font-bold text-[#111111] leading-tight">Operations Dashboard</h2>
            <p class="text-[13px] text-[#888888] mt-0.5">Review approvals, monitor fulfillment, and track cash.</p>
        </div>
        <a href="{{ route('orders.create') }}" wire:navigate
           class="flex-shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 text-[12px] font-semibold text-white bg-[#111111] hover:bg-[#333333] rounded-xl transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            New Order
        </a>
    </div>

    {{-- Manager KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 bg-orange-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                @if($pendingApprovalCount > 0)
                    <span class="text-[11px] font-bold text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full">Action Needed</span>
                @endif
            </div>
            <div class="text-[22px] font-black {{ $pendingApprovalCount > 0 ? 'text-orange-600' : 'text-[#111111]' }} tabular-nums">{{ $pendingApprovalCount }}</div>
            <div class="text-[12px] text-[#888888] mt-0.5">Pending Approval</div>
            @if($pendingApprovalCount > 0)
                <a href="{{ route('orders.index', ['selectedApproval' => 'pending_approval']) }}" wire:navigate class="text-[11px] text-orange-600 font-semibold hover:text-orange-700 mt-1 block">Review now →</a>
            @endif
            <details class="mt-2 text-[11px] text-[#666666]"><summary class="cursor-pointer font-semibold">ⓘ What should I do?</summary><p class="mt-1">Review these staff orders. Approve valid orders or reject them with a clear reason.</p></details>
        </div>

        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="w-9 h-9 bg-[#F5F5F5] rounded-lg flex items-center justify-center mb-3">
                <svg class="w-4 h-4 text-[#666666]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <div class="text-[22px] font-black text-[#111111] tabular-nums">{{ $ordersToFulfilCount }}</div>
            <div class="text-[12px] text-[#888888] mt-0.5">Orders to Fulfil</div>
            <details class="mt-2 text-[11px] text-[#666666]"><summary class="cursor-pointer font-semibold">ⓘ What should I do?</summary><p class="mt-1">These approved orders are confirmed or being prepared. Update each one as it moves through fulfillment.</p></details>
        </div>

        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="w-9 h-9 bg-[#F5F5F5] rounded-lg flex items-center justify-center mb-3">
                <svg class="w-4 h-4 text-[#666666]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="text-[22px] font-black text-[#111111] tabular-nums">₱{{ number_format($currentCash, 0) }}</div>
            <div class="text-[12px] text-[#888888] mt-0.5">Cash Position</div>
            <details class="mt-2 text-[11px] text-[#666666]"><summary class="cursor-pointer font-semibold">ⓘ What does this show?</summary><p class="mt-1">Recorded business cash from sales, expenses, and withdrawals. It may exclude offline records that have not synchronized.</p></details>
        </div>

        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="w-9 h-9 bg-[#F5F5F5] rounded-lg flex items-center justify-center mb-3">
                <svg class="w-4 h-4 {{ $lowStockCount > 0 ? 'text-red-500' : 'text-[#666666]' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div class="text-[22px] font-black {{ $lowStockCount > 0 ? 'text-red-600' : 'text-[#111111]' }} tabular-nums">{{ $lowStockCount }}</div>
            <div class="text-[12px] text-[#888888] mt-0.5">Low Stock Items</div>
            <details class="mt-2 text-[11px] text-[#666666]"><summary class="cursor-pointer font-semibold">ⓘ What should I do?</summary><p class="mt-1">Check these items and restock or adjust inventory before they prevent customer orders.</p></details>
        </div>
    </div>

    {{-- Manager Finance Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="text-[12px] text-[#888888] mb-1">Today's Income</div>
            <div class="text-[20px] font-black text-[#111111] tabular-nums">₱{{ number_format($todayIncome, 0) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="text-[12px] text-[#888888] mb-1">Today's Expenses</div>
            <div class="text-[20px] font-black text-[#111111] tabular-nums">₱{{ number_format($todayExpenses, 0) }}</div>
        </div>
        <div class="col-span-2 lg:col-span-1 bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="text-[12px] text-[#888888] mb-1">Monthly Revenue</div>
            <div class="text-[20px] font-black text-[#111111] tabular-nums">₱{{ number_format($monthlyRevenue, 0) }}</div>
        </div>
    </div>

    {{-- Manager: Low Stock Alerts + Recent Activity --}}
    @include('livewire.dashboard._partials.alerts-and-activity')

    {{-- ===================================================================
         OWNER DASHBOARD
         =================================================================== --}}
    @else

    {{-- Greeting --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-[22px] font-bold text-[#111111] leading-tight">Business Command Center</h2>
            <p class="text-[13px] text-[#888888] mt-0.5">Real-time overview of your fashion ecosystem operations.</p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <button class="flex items-center gap-1.5 px-3.5 py-2 text-[12px] font-semibold text-[#444444] bg-white border border-[#E0E0E0] rounded-xl hover:bg-[#F5F5F5] transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Last 24 Hours
            </button>
            <button class="flex items-center gap-1.5 px-3.5 py-2 text-[12px] font-semibold text-white bg-[#111111] hover:bg-[#333333] rounded-xl transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export
            </button>
        </div>
    </div>

    {{-- Owner KPI Row 1 --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 bg-[#F5F5F5] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#666666]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
                <span class="text-[11px] font-bold text-emerald-600">+{{ number_format($todayIncome > 0 ? 12.5 : 0, 1) }}%</span>
            </div>
            <div class="text-[18px] font-bold text-[#111111] tabular-nums">₱{{ number_format($todayIncome, 2) }}</div>
            <div class="text-[12px] text-[#888888] mt-0.5">Today's Sales</div>
        </div>

        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 bg-[#F5F5F5] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#666666]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <span class="text-[11px] font-bold text-emerald-600">+8.2%</span>
            </div>
            <div class="text-[18px] font-bold text-[#111111] tabular-nums">₱{{ number_format($monthlyRevenue, 2) }}</div>
            <div class="text-[12px] text-[#888888] mt-0.5">Monthly Revenue</div>
        </div>

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

    {{-- Owner KPI Row 2 --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="text-[18px] font-bold text-[#111111] tabular-nums mb-1">₱{{ number_format($inventoryValue, 0) }}</div>
            <div class="text-[12px] text-[#888888] mb-3">Inventory Value</div>
            <div class="w-full bg-[#F0F0F0] rounded-full h-1.5"><div class="bg-[#111111] h-1.5 rounded-full" style="width: 75%"></div></div>
        </div>

        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="text-[18px] font-bold {{ $pendingApprovalCount > 0 ? 'text-orange-600' : 'text-[#111111]' }} tabular-nums mb-1">{{ $pendingApprovalCount }}</div>
            <div class="text-[12px] text-[#888888] mb-2">Pending Approvals</div>
            @if($pendingApprovalCount > 0)
                <a href="{{ route('orders.index') }}" wire:navigate class="text-[11px] font-semibold text-orange-600 hover:text-orange-700">Review now →</a>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="text-[18px] font-bold text-[#111111] tabular-nums mb-1">{{ $ordersToFulfilCount }}</div>
            <div class="text-[12px] text-[#888888] mb-2">Orders to Fulfil</div>
            <div class="flex items-center gap-1 text-[11px] text-[#888888]">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Ready to process
            </div>
        </div>

        <div class="bg-white rounded-xl border border-[#E8E8E8] p-5">
            <div class="text-[18px] font-bold {{ $lowStockCount > 0 ? 'text-red-600' : 'text-[#111111]' }} tabular-nums mb-1">{{ $lowStockCount }}</div>
            <div class="text-[12px] text-[#888888] mb-2">Low Stock Items</div>
            @if($lowStockCount > 0)
                <a href="{{ route('inventory.index') }}" wire:navigate class="text-[11px] font-semibold text-red-500 hover:text-red-700">View inventory →</a>
            @else
                <div class="text-[11px] text-emerald-600 font-semibold">Stock levels healthy</div>
            @endif
        </div>
    </div>

    {{-- Owner: Alerts + Activity --}}
    @include('livewire.dashboard._partials.alerts-and-activity')

    @endif
</div>
