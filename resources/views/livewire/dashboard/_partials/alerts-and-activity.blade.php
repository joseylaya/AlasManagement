{{-- Shared between Manager and Owner dashboards --}}
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
                @if($pendingApprovalCount > 0)
                <li class="flex items-start gap-3 px-5 py-3.5">
                    <div class="w-1.5 h-1.5 rounded-full bg-orange-500 mt-1.5 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[12px] font-semibold text-[#111111]">{{ $pendingApprovalCount }} Order{{ $pendingApprovalCount > 1 ? 's' : '' }} Pending Approval</div>
                        <a href="{{ route('orders.index') }}" wire:navigate class="text-[11px] text-orange-600 font-semibold hover:text-orange-700">Review now →</a>
                    </div>
                </li>
                @endif

                @forelse($lowStockItems as $inv)
                <li class="flex items-start gap-3 px-5 py-3.5">
                    <div class="w-1.5 h-1.5 rounded-full bg-red-500 mt-1.5 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[12px] font-semibold text-[#111111]">
                            Low Stock: {{ $inv->product->product_name }}
                            @if($inv->product->size) ({{ $inv->product->size }}) @endif
                        </div>
                        <div class="text-[11px] text-[#888888] mt-0.5">
                            {{ $inv->current_stock }} units remaining. Min: {{ $inv->min_stock_threshold }}.
                        </div>
                    </div>
                </li>
                @empty
                    @if($pendingApprovalCount === 0)
                    <li class="px-5 py-8 text-center">
                        <div class="text-[12px] text-[#888888]">No critical alerts at this time.</div>
                    </li>
                    @endif
                @endforelse
            </ul>

            @if($lowStockItems->count() > 0)
            <div class="px-5 py-3 border-t border-[#F0F0F0]">
                <a href="{{ route('inventory.index') }}" wire:navigate
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
                <a href="{{ route('orders.create') }}" wire:navigate
                   class="flex items-center justify-between px-3 py-3 rounded-xl hover:bg-[#F5F5F5] transition-colors group">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 bg-[#F0F0F0] rounded-lg flex items-center justify-center group-hover:bg-[#E8E8E8] transition-colors">
                            <svg class="w-3.5 h-3.5 text-[#555555]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                        <span class="text-[13px] font-semibold text-[#333333]">Create New Order</span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-[#BBBBBB]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                </a>

                @if(auth()->user()->canManageProducts())
                <a href="{{ route('products.create') }}" wire:navigate
                   class="flex items-center justify-between px-3 py-3 rounded-xl hover:bg-[#F5F5F5] transition-colors group">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 bg-[#F0F0F0] rounded-lg flex items-center justify-center group-hover:bg-[#E8E8E8] transition-colors">
                            <svg class="w-3.5 h-3.5 text-[#555555]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        </div>
                        <span class="text-[13px] font-semibold text-[#333333]">Add Product</span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-[#BBBBBB]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                </a>
                @endif

                @if(auth()->user()->canAccessFinance())
                <a href="{{ route('finance.index') }}" wire:navigate
                   class="flex items-center justify-between px-3 py-3 rounded-xl hover:bg-[#F5F5F5] transition-colors group">
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
                <a href="{{ route('activity-logs.index') }}" wire:navigate class="text-[12px] font-semibold text-[#888888] hover:text-[#111111] transition-colors">
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
                            <span class="text-[12px] font-bold text-[#111111]">{{ $log->user ? $log->user->name : 'System' }}</span>
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
