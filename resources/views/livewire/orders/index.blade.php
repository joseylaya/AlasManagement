<div class="space-y-5">

    {{-- ===== PAGE HEADER ===== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-[20px] font-bold text-[#111111] leading-tight">Orders & Fulfillment</h2>
            <p class="text-[13px] text-[#888888] mt-0.5">
                @if($isStaff) Your submitted orders @else All customer orders @endif
                @if($pendingApprovalCount > 0 && $canApprove)
                    · <span class="text-orange-600 font-semibold">{{ $pendingApprovalCount }} pending approval</span>
                @endif
            </p>
        </div>
        <a href="{{ route('orders.create') }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#111111] hover:bg-[#333333] text-white text-[13px] font-semibold rounded-xl transition-colors self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            {{ $isStaff ? 'New Order' : 'Create Order' }}
        </a>
    </div>

    {{-- ===== APPROVAL TABS (Manager/Owner only) ===== --}}
    @if($canApprove)
    <div class="flex gap-2 overflow-x-auto pb-1">
        <button wire:click="$set('selectedApproval', '')"
                class="flex-shrink-0 px-3.5 py-1.5 text-[12px] font-semibold rounded-full transition-colors
                       {{ $selectedApproval === '' ? 'bg-[#111111] text-white' : 'bg-white border border-[#E0E0E0] text-[#555555] hover:bg-[#F5F5F5]' }}">
            All Orders
        </button>
        <button wire:click="$set('selectedApproval', 'pending_approval')"
                class="flex-shrink-0 px-3.5 py-1.5 text-[12px] font-semibold rounded-full transition-colors
                       {{ $selectedApproval === 'pending_approval' ? 'bg-orange-500 text-white' : 'bg-white border border-[#E0E0E0] text-[#555555] hover:bg-[#F5F5F5]' }}">
            Pending Approval
            @if($pendingApprovalCount > 0)
                <span class="ml-1 {{ $selectedApproval === 'pending_approval' ? 'bg-white/30' : 'bg-orange-100 text-orange-600' }} text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $pendingApprovalCount }}</span>
            @endif
        </button>
        <button wire:click="$set('selectedApproval', 'approved')"
                class="flex-shrink-0 px-3.5 py-1.5 text-[12px] font-semibold rounded-full transition-colors
                       {{ $selectedApproval === 'approved' ? 'bg-emerald-600 text-white' : 'bg-white border border-[#E0E0E0] text-[#555555] hover:bg-[#F5F5F5]' }}">
            Approved
        </button>
        <button wire:click="$set('selectedApproval', 'rejected')"
                class="flex-shrink-0 px-3.5 py-1.5 text-[12px] font-semibold rounded-full transition-colors
                       {{ $selectedApproval === 'rejected' ? 'bg-red-500 text-white' : 'bg-white border border-[#E0E0E0] text-[#555555] hover:bg-[#F5F5F5]' }}">
            Rejected
        </button>
    </div>
    @endif

    {{-- ===== SEARCH + FILTERS ===== --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#AAAAAA]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input wire:model.live.debounce.300ms="search"
                   type="text" placeholder="Search order #, customer, phone…"
                   class="w-full pl-9 pr-4 py-2.5 text-[13px] bg-white border border-[#E0E0E0] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#111111]/10 focus:border-[#111111]">
        </div>
        <div class="flex gap-2">
            <select wire:model.live="selectedStatus"
                    class="px-3 py-2.5 text-[13px] bg-white border border-[#E0E0E0] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#111111]/10 min-w-[130px]">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="preparing">Preparing</option>
                <option value="packed">Packed</option>
                <option value="shipped">Shipped</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select wire:model.live="selectedDelivery"
                    class="px-3 py-2.5 text-[13px] bg-white border border-[#E0E0E0] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#111111]/10 min-w-[120px]">
                <option value="">All Methods</option>
                <option value="shipping">Shipping</option>
                <option value="meetup">Meet-up</option>
            </select>
        </div>
    </div>

    {{-- ===================================================================
         MOBILE VIEW: Stacked Cards  (hidden on md+)
         =================================================================== --}}
    <div class="md:hidden space-y-3">
        @forelse($orders as $order)
        @php
            $statusMap = [
                'pending'    => ['bg' => 'bg-gray-100',    'text' => 'text-gray-600',    'dot' => 'bg-gray-400'],
                'confirmed'  => ['bg' => 'bg-blue-50',     'text' => 'text-blue-700',    'dot' => 'bg-blue-500'],
                'preparing'  => ['bg' => 'bg-indigo-50',   'text' => 'text-indigo-700',  'dot' => 'bg-indigo-500'],
                'packed'     => ['bg' => 'bg-purple-50',   'text' => 'text-purple-700',  'dot' => 'bg-purple-500'],
                'shipped'    => ['bg' => 'bg-sky-50',      'text' => 'text-sky-700',     'dot' => 'bg-sky-500'],
                'completed'  => ['bg' => 'bg-emerald-50',  'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500'],
                'cancelled'  => ['bg' => 'bg-red-50',      'text' => 'text-red-600',     'dot' => 'bg-red-500'],
            ];
            $approvalMap = [
                'pending_approval' => ['bg' => 'bg-orange-50',  'text' => 'text-orange-700'],
                'approved'         => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700'],
                'rejected'         => ['bg' => 'bg-red-50',     'text' => 'text-red-700'],
            ];
            $payMap = [
                'pending'  => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700'],
                'partial'  => ['bg' => 'bg-orange-50', 'text' => 'text-orange-700'],
                'paid'     => ['bg' => 'bg-emerald-50','text' => 'text-emerald-700'],
                'refunded' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700'],
            ];
            $s = $statusMap[$order->order_status]   ?? ['bg'=>'bg-gray-100','text'=>'text-gray-600','dot'=>'bg-gray-400'];
            $a = $approvalMap[$order->approval_status] ?? ['bg'=>'bg-gray-50','text'=>'text-gray-600'];
            $p = $payMap[$order->payment_status]    ?? ['bg'=>'bg-gray-50','text'=>'text-gray-600'];
        @endphp

        <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-sm overflow-hidden">

            {{-- Card top row --}}
            <div class="px-4 pt-4 pb-3 flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <span class="text-[11px] font-mono font-bold text-[#AAAAAA]">{{ $order->order_number }}</span>
                    <div class="text-[15px] font-bold text-[#111111] leading-tight mt-0.5 truncate">{{ $order->customer_name }}</div>
                    @if($order->customer_phone)
                        <div class="text-[12px] text-[#888888] mt-0.5">{{ $order->customer_phone }}</div>
                    @endif
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="text-[18px] font-black text-[#111111] tabular-nums">₱{{ number_format($order->total_amount, 0) }}</div>
                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold {{ $p['bg'] }} {{ $p['text'] }} px-2 py-0.5 rounded-full mt-0.5">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </div>
            </div>

            {{-- Approval status (prominent if pending) --}}
            @if($order->approval_status === 'pending_approval')
            <div class="mx-4 mb-3 flex items-center gap-2 px-3 py-2 bg-orange-50 border border-orange-200 rounded-xl">
                <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span class="text-[12px] font-bold text-orange-700">Pending Approval</span>
            </div>
            @elseif($order->approval_status === 'rejected')
            <div class="mx-4 mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-xl">
                <div class="text-[12px] font-bold text-red-700">Rejected</div>
                @if($order->rejection_reason)
                    <div class="text-[11px] text-red-500 mt-0.5">{{ $order->rejection_reason }}</div>
                @endif
            </div>
            @endif

            {{-- Info badges row --}}
            <div class="px-4 pb-3 flex flex-wrap gap-1.5">
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold {{ $s['bg'] }} {{ $s['text'] }} px-2.5 py-1 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full {{ $s['dot'] }}"></span>
                    {{ ucfirst($order->order_status) }}
                </span>
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold bg-[#F5F5F5] text-[#555555] px-2.5 py-1 rounded-full">
                    @if($order->delivery_method === 'shipping')
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8z"/></svg>
                    @else
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3"/></svg>
                    @endif
                    {{ ucfirst($order->delivery_method) }}
                </span>
                @if($order->items->count() > 0)
                <span class="text-[11px] text-[#888888] px-2.5 py-1 bg-[#F5F5F5] rounded-full">
                    {{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}
                </span>
                @endif
            </div>

            {{-- Footer row --}}
            <div class="px-4 pb-3 flex items-center justify-between gap-2 border-t border-[#F5F5F5] pt-3">
                <div class="text-[11px] text-[#AAAAAA]">
                    @if($order->creator)
                        by {{ $order->creator->name }} ·
                    @endif
                    {{ $order->created_at->format('M j, Y') }}
                </div>
                <div class="flex items-center gap-2">
                    {{-- Approve / Reject (Manager/Owner, pending only) --}}
                    @if($canApprove && $order->isPendingApproval())
                        <button wire:click="approveOrder({{ $order->id }})"
                                class="min-h-[36px] px-3 py-1.5 text-[12px] font-bold bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors">
                            ✓ Approve
                        </button>
                        <button wire:click="openRejectModal({{ $order->id }})"
                                class="min-h-[36px] px-3 py-1.5 text-[12px] font-bold bg-red-50 text-red-600 border border-red-200 rounded-xl hover:bg-red-100 transition-colors">
                            ✕ Reject
                        </button>
                    @endif
                    <a href="{{ route('orders.show', $order->id) }}" wire:navigate
                       class="min-h-[36px] inline-flex items-center gap-1 px-3.5 py-1.5 text-[12px] font-bold bg-[#111111] text-white rounded-xl hover:bg-[#333333] transition-colors">
                        View
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-[#EEEEEE] p-10 text-center">
            <svg class="w-10 h-10 text-[#DDDDDD] mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <div class="text-[14px] font-semibold text-[#AAAAAA]">No orders found</div>
            <p class="text-[12px] text-[#CCCCCC] mt-1">Try adjusting your search or filters</p>
        </div>
        @endforelse

        {{-- Pagination --}}
        <div class="pt-2">{{ $orders->links() }}</div>
    </div>

    {{-- ===================================================================
         DESKTOP VIEW: Table  (hidden on mobile)
         =================================================================== --}}
    <div class="hidden md:block bg-white rounded-2xl border border-[#E8E8E8] overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#F0F0F0]">
                    <th class="px-5 py-3.5 text-left text-[11px] font-bold text-[#888888] uppercase tracking-wide">Order</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-bold text-[#888888] uppercase tracking-wide">Customer</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-bold text-[#888888] uppercase tracking-wide">Approval</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-bold text-[#888888] uppercase tracking-wide">Status</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-bold text-[#888888] uppercase tracking-wide">Payment</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-bold text-[#888888] uppercase tracking-wide">Total</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-bold text-[#888888] uppercase tracking-wide">Delivery</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-bold text-[#888888] uppercase tracking-wide">Date</th>
                    <th class="px-5 py-3.5 text-right text-[11px] font-bold text-[#888888] uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#F5F5F5]">
                @forelse($orders as $order)
                @php
                    $s = $statusMap[$order->order_status]      ?? ['bg'=>'bg-gray-100','text'=>'text-gray-600','dot'=>'bg-gray-400'];
                    $a = $approvalMap[$order->approval_status] ?? ['bg'=>'bg-gray-50','text'=>'text-gray-600'];
                    $p = $payMap[$order->payment_status]       ?? ['bg'=>'bg-gray-50','text'=>'text-gray-600'];
                @endphp
                <tr class="hover:bg-[#FAFAFA] transition-colors">
                    <td class="px-5 py-3.5">
                        <span class="text-[12px] font-mono font-bold text-[#333333]">{{ $order->order_number }}</span>
                        @if($order->items->count() > 0)
                            <div class="text-[11px] text-[#888888]">{{ $order->items->count() }} items</div>
                        @endif
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="text-[13px] font-semibold text-[#111111]">{{ $order->customer_name }}</div>
                        @if($order->customer_phone)
                            <div class="text-[11px] text-[#888888]">{{ $order->customer_phone }}</div>
                        @endif
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center text-[11px] font-semibold {{ $a['bg'] }} {{ $a['text'] }} px-2 py-0.5 rounded-full">
                            {{ $order->approvalStatusLabel() }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold {{ $s['bg'] }} {{ $s['text'] }} px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full {{ $s['dot'] }}"></span>
                            {{ ucfirst($order->order_status) }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="inline-flex text-[11px] font-semibold {{ $p['bg'] }} {{ $p['text'] }} px-2 py-0.5 rounded-full">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-[13px] font-bold text-[#111111] tabular-nums">
                        ₱{{ number_format($order->total_amount, 2) }}
                    </td>
                    <td class="px-5 py-3.5 text-[12px] text-[#555555] capitalize">{{ $order->delivery_method }}</td>
                    <td class="px-5 py-3.5 text-[12px] text-[#888888]">{{ $order->created_at->format('M j, Y') }}</td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center justify-end gap-2">
                            @if($canApprove && $order->isPendingApproval())
                                <button wire:click="approveOrder({{ $order->id }})"
                                        class="px-2.5 py-1.5 text-[11px] font-bold bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                                    Approve
                                </button>
                                <button wire:click="openRejectModal({{ $order->id }})"
                                        class="px-2.5 py-1.5 text-[11px] font-bold text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                                    Reject
                                </button>
                            @endif
                            @if($canManageFulfillment && $order->isApproved() && ! in_array($order->order_status, ['completed','cancelled']))
                                @php
                                    $nextStatus = match($order->order_status) {
                                        'pending'    => 'confirmed',
                                        'confirmed'  => 'preparing',
                                        'preparing'  => 'packed',
                                        'packed'     => $order->delivery_method === 'meetup' ? 'completed' : 'shipped',
                                        'shipped'    => 'completed',
                                        default      => null,
                                    };
                                @endphp
                                @if($nextStatus)
                                <button wire:click="updateStatus({{ $order->id }}, '{{ $nextStatus }}')"
                                        class="px-2.5 py-1.5 text-[11px] font-bold text-[#555555] bg-[#F5F5F5] border border-[#E0E0E0] rounded-lg hover:bg-[#EBEBEB] transition-colors capitalize">
                                    → {{ $nextStatus }}
                                </button>
                                @endif
                            @endif
                            <a href="{{ route('orders.show', $order->id) }}" wire:navigate
                               class="px-2.5 py-1.5 text-[11px] font-bold text-white bg-[#111111] rounded-lg hover:bg-[#333333] transition-colors">
                                View
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-5 py-16 text-center">
                        <svg class="w-10 h-10 text-[#DDDDDD] mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <div class="text-[14px] font-semibold text-[#AAAAAA]">No orders found</div>
                        <p class="text-[12px] text-[#CCCCCC] mt-1">Try adjusting your search or filters</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Desktop Pagination --}}
        @if($orders->hasPages())
        <div class="px-5 py-4 border-t border-[#F0F0F0]">{{ $orders->links() }}</div>
        @endif
    </div>

    {{-- ===== REJECT MODAL ===== --}}
    @if($showRejectModal)
    <div class="fixed inset-0 z-[90] flex items-end sm:items-center justify-center p-3 sm:p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" x-data>
            <div class="px-6 pt-6 pb-4 border-b border-[#F0F0F0]">
                <h3 class="text-[16px] font-bold text-[#111111]">Reject Order</h3>
                <p class="text-[13px] text-[#888888] mt-0.5">Please provide a reason for rejection.</p>
            </div>
            <div class="p-6">
                <label class="block text-[12px] font-semibold text-[#444444] mb-2">Rejection Reason <span class="text-red-500">*</span></label>
                <textarea wire:model="rejectReason" rows="4"
                          placeholder="Explain why this order is being rejected…"
                          class="w-full px-4 py-3 text-[13px] border border-[#E0E0E0] rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-400"></textarea>
            </div>
            <div class="px-6 pb-6 flex gap-3">
                <button type="button" wire:click="closeRejectModal"
                        class="flex-1 py-3 text-[13px] font-semibold text-[#555555] bg-white border border-[#E0E0E0] rounded-xl hover:bg-[#F5F5F5] transition-colors">
                    Cancel
                </button>
                <button type="button" wire:click="confirmReject" wire:loading.attr="disabled"
                        class="flex-1 py-3 text-[13px] font-semibold text-white bg-red-500 rounded-xl hover:bg-red-600 transition-colors">
                    Reject Order
                </button>
            </div>
        </div>
    </div>
    @endif

</div>

@php
// Status color maps for desktop table (defined here to be reusable)
$statusMap = $statusMap ?? [
    'pending'    => ['bg' => 'bg-gray-100',    'text' => 'text-gray-600',    'dot' => 'bg-gray-400'],
    'confirmed'  => ['bg' => 'bg-blue-50',     'text' => 'text-blue-700',    'dot' => 'bg-blue-500'],
    'preparing'  => ['bg' => 'bg-indigo-50',   'text' => 'text-indigo-700',  'dot' => 'bg-indigo-500'],
    'packed'     => ['bg' => 'bg-purple-50',   'text' => 'text-purple-700',  'dot' => 'bg-purple-500'],
    'shipped'    => ['bg' => 'bg-sky-50',      'text' => 'text-sky-700',     'dot' => 'bg-sky-500'],
    'completed'  => ['bg' => 'bg-emerald-50',  'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500'],
    'cancelled'  => ['bg' => 'bg-red-50',      'text' => 'text-red-600',     'dot' => 'bg-red-500'],
];
$approvalMap = $approvalMap ?? [
    'pending_approval' => ['bg' => 'bg-orange-50',  'text' => 'text-orange-700'],
    'approved'         => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700'],
    'rejected'         => ['bg' => 'bg-red-50',     'text' => 'text-red-700'],
];
$payMap = $payMap ?? [
    'pending'  => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700'],
    'partial'  => ['bg' => 'bg-orange-50', 'text' => 'text-orange-700'],
    'paid'     => ['bg' => 'bg-emerald-50','text' => 'text-emerald-700'],
    'refunded' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700'],
];
@endphp
