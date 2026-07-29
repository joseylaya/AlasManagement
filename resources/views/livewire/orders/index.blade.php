<div class="space-y-6">

    <!-- Header Actions & Filters -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Orders & Fulfillment Queue</h2>
                <p class="text-xs text-slate-500">Manage customer sales, status workflow, and delivery logistics</p>
            </div>
            <a href="{{ route('orders.create') }}" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-sm rounded-xl shadow-sm transition-colors flex items-center justify-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create New Order
            </a>
        </div>

        <!-- Search and Filter Bar -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
            <div>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Search order #, customer name..." 
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white"
                >
            </div>

            <div>
                <select wire:model.live="selectedStatus" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="packed">Packed</option>
                    <option value="shipped">Shipped</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div>
                <select wire:model.live="selectedDelivery" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white">
                    <option value="">All Delivery Types</option>
                    <option value="shipping">Courier Shipping</option>
                    <option value="meetup">Meetup Location</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Orders Table Card -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Order #</th>
                        <th class="px-6 py-3.5">Customer & Phone</th>
                        <th class="px-6 py-3.5">Delivery Info</th>
                        <th class="px-6 py-3.5">Total Amount</th>
                        <th class="px-6 py-3.5">Order Status</th>
                        <th class="px-6 py-3.5">Payment</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-mono font-extrabold text-slate-900">
                                <a href="{{ route('orders.show', $order->id) }}" class="hover:text-amber-600 underline decoration-amber-400">
                                    {{ $order->order_number }}
                                </a>
                                <div class="text-[10px] text-slate-400 font-sans font-normal">{{ $order->created_at->format('M d, Y') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">{{ $order->customer_name }}</div>
                                <div class="text-xs text-slate-500">{{ $order->customer_phone ?? 'No phone' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-xs text-slate-800 capitalize">{{ $order->delivery_method }}</div>
                                @if($order->delivery_method === 'meetup')
                                    <div class="text-[11px] text-purple-700 font-medium">📍 {{ $order->meetup_location }} ({{ $order->meetup_date ? $order->meetup_date->format('M d') : '' }})</div>
                                @else
                                    <div class="text-[11px] text-slate-500 truncate max-w-xs">{{ Str::limit($order->shipping_address, 30) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-black text-slate-900 text-base">
                                ₱{{ number_format($order->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4">
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
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full border uppercase tracking-wider {{ $statusColors[$order->order_status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $order->order_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 text-xs font-bold rounded {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-1.5">
                                <a href="{{ route('orders.show', $order->id) }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-lg transition-colors">
                                    View Details
                                </a>

                                @if($order->order_status === 'pending')
                                    <button wire:click="updateStatus({{ $order->id }}, 'confirmed')" class="px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-bold rounded-lg">Confirm</button>
                                @elseif($order->order_status === 'confirmed')
                                    <button wire:click="updateStatus({{ $order->id }}, 'packed')" class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 text-xs font-bold rounded-lg">Pack</button>
                                @elseif($order->order_status === 'packed')
                                    <button wire:click="updateStatus({{ $order->id }}, 'shipped')" class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold rounded-lg">Ship</button>
                                @elseif($order->order_status === 'shipped')
                                    <button wire:click="updateStatus({{ $order->id }}, 'completed')" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold rounded-lg">Complete</button>
                                @endif

                                @if(!in_array($order->order_status, ['completed', 'cancelled']))
                                    <button wire:click="cancelOrder({{ $order->id }})" wire:confirm="Cancel order {{ $order->order_number }} and restore stock?" class="px-2 py-1 text-rose-600 hover:text-rose-800 text-xs font-bold">Cancel</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                No orders matching your search query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</div>
