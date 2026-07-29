<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-3">
                <h2 class="text-2xl font-black text-slate-900 font-mono">{{ $order->order_number }}</h2>
                @php
                    $statusColors = [
                        'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'confirmed' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'packed' => 'bg-purple-50 text-purple-700 border-purple-200',
                        'shipped' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                    ];
                @endphp
                <span class="px-3 py-1 text-xs font-bold rounded-full border uppercase tracking-wider {{ $statusColors[$order->order_status] ?? 'bg-slate-100' }}">
                    {{ $order->order_status }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Created on {{ $order->created_at->format('F d, Y \a\t g:i A') }} by {{ $order->user->name ?? 'System' }}</p>
        </div>

        <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors">
            ← Back to Queue
        </a>
    </div>

    <!-- Status Lifecycle Controls Bar -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Fulfillment Actions</span>
            <span class="text-sm font-semibold text-slate-800">Current Status: <span class="capitalize font-bold text-slate-900">{{ $order->order_status }}</span></span>
        </div>

        <div class="flex items-center space-x-2">
            @if($order->order_status === 'pending')
                <button wire:click="updateStatus('confirmed')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow">Confirm Order</button>
            @elseif($order->order_status === 'confirmed')
                <button wire:click="updateStatus('packed')" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow">Mark as Packed</button>
            @elseif($order->order_status === 'packed')
                <button wire:click="updateStatus('shipped')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow">Mark as Shipped</button>
            @elseif($order->order_status === 'shipped')
                <button wire:click="updateStatus('completed')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow">Complete Order & Record Cash</button>
            @endif

            @if(!in_array($order->order_status, ['completed', 'cancelled']))
                <button wire:click="cancelOrder" wire:confirm="Cancel order and restore inventory stock?" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold rounded-xl">Cancel Order</button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

        <!-- Customer & Delivery Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">Customer & Logistics</h3>
            
            <div class="space-y-2 text-sm">
                <div>
                    <span class="text-xs font-semibold text-slate-500 block">Customer Name</span>
                    <span class="font-bold text-slate-900">{{ $order->customer_name }}</span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-500 block">Phone / Contact</span>
                    <span class="font-medium text-slate-800">{{ $order->customer_phone ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-500 block">Delivery Method</span>
                    <span class="font-bold text-slate-900 capitalize">{{ $order->delivery_method }}</span>
                </div>

                @if($order->delivery_method === 'shipping')
                    <div>
                        <span class="text-xs font-semibold text-slate-500 block">Shipping Address</span>
                        <span class="text-xs text-slate-700">{{ $order->shipping_address ?? 'No address specified' }}</span>
                    </div>
                @else
                    <div>
                        <span class="text-xs font-semibold text-slate-500 block">Meetup Location & Date</span>
                        <span class="text-xs font-bold text-purple-700">📍 {{ $order->meetup_location }} ({{ $order->meetup_date ? $order->meetup_date->format('M d, Y') : 'TBD' }})</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Payment & Audit Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">Payment Details</h3>
            
            <div class="space-y-2 text-sm">
                <div>
                    <span class="text-xs font-semibold text-slate-500 block">Payment Method</span>
                    <span class="font-bold text-slate-900">{{ $order->payment_method }}</span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-500 block">Payment Status</span>
                    <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-500 block">Cash Ledger Entry</span>
                    @if($order->cashTransactions->isNotEmpty())
                        <span class="text-xs font-mono font-bold text-emerald-600">CTX: {{ $order->cashTransactions->first()->transaction_number }} (₱{{ number_format($order->cashTransactions->first()->amount, 2) }})</span>
                    @else
                        <span class="text-xs text-slate-400">Pending order completion</span>
                    @endif
                </div>
                @if($order->notes)
                <div>
                    <span class="text-xs font-semibold text-slate-500 block">Order Notes</span>
                    <span class="text-xs text-slate-600 bg-slate-50 p-2 rounded-lg block border border-slate-100">{{ $order->notes }}</span>
                </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Purchased Line Items Card -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ordered Items Price Snapshot</h3>
        </div>

        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    <th class="px-6 py-3.5">Item Name</th>
                    <th class="px-6 py-3.5">SKU</th>
                    <th class="px-6 py-3.5">Unit Price</th>
                    <th class="px-6 py-3.5">Quantity</th>
                    <th class="px-6 py-3.5 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                @foreach($order->items as $item)
                    <tr>
                        <td class="px-6 py-4 font-bold text-slate-900">{{ $item->product_name }}</td>
                        <td class="px-6 py-4 font-mono text-xs text-slate-600">{{ $item->sku }}</td>
                        <td class="px-6 py-4">₱{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-6 py-4 font-bold">{{ $item->quantity }}</td>
                        <td class="px-6 py-4 text-right font-black text-slate-900">₱{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
            <span class="text-sm font-bold text-slate-600">Total Order Value</span>
            <span class="text-2xl font-black text-slate-900">₱{{ number_format($order->total_amount, 2) }}</span>
        </div>
    </div>

</div>
