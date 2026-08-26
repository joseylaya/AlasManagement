<div class="max-w-5xl mx-auto space-y-6">

    <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900">Create Customer Order</h2>
            <p class="text-xs text-slate-500">Record a new sales transaction with automatic stock deduction and cash logging</p>
        </div>
        <a href="{{ route('orders.index') }}" wire:navigate class="inline-flex min-h-[44px] items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors">
            ← Back to Orders
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Cart Items & Product Selector -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Select Product Card -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm space-y-4 sm:p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Add Products to Order</h3>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Select Apparel Product</label>
                        <select wire:model="selectedProductId" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white font-medium">
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">
                                    {{ $p->product_name }} ({{ $p->sku }}) — ₱{{ number_format($p->selling_price, 2) }} [Stock: {{ $p->current_stock }}]
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Quantity</label>
                        <input type="number" min="1" wire:model="selectedQuantity" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500 focus:bg-white">
                    </div>

                    <div class="flex items-end">
                        <button type="button" wire:click="addItem" wire:loading.attr="disabled" wire:target="addItem" class="min-h-[44px] w-full py-2.5 bg-amber-500 hover:bg-amber-600 disabled:cursor-wait disabled:opacity-60 text-slate-950 font-bold text-sm rounded-xl shadow transition-colors">
                            <span wire:loading.remove wire:target="addItem">+ Add Line</span>
                            <span wire:loading wire:target="addItem" class="inline-flex items-center gap-2"><svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="8" stroke="currentColor" stroke-width="3"/><path class="opacity-90" fill="currentColor" d="M12 4a8 8 0 0 1 8 8h-3a5 5 0 0 0-5-5V4z"/></svg>Adding…</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cart Line Items Table -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Order Items Summary</h3>
                    <span class="text-xs font-bold text-slate-700">{{ count($cartItems) }} Item(s)</span>
                </div>

                <div class="divide-y divide-slate-100 md:hidden">
                    @forelse($cartItems as $index => $item)
                        <article class="space-y-3 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900">{{ $item['name'] }}</p>
                                    <p class="mt-0.5 font-mono text-[11px] text-slate-500">{{ $item['sku'] }}</p>
                                </div>
                                <p class="shrink-0 text-base font-black text-slate-900">₱{{ number_format($item['subtotal'], 2) }}</p>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                                <p class="text-xs text-slate-600">₱{{ number_format($item['unit_price'], 2) }} × <span class="font-bold">{{ $item['quantity'] }}</span></p>
                                <button type="button" wire:click="removeItem({{ $index }})" class="min-h-[40px] rounded-xl px-3 text-xs font-bold text-rose-700 hover:bg-rose-50">Remove</button>
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-10 text-center text-sm text-slate-400">
                            No items added yet. Choose a product above to begin.
                        </div>
                    @endforelse
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th class="px-6 py-3">Product Name</th>
                                <th class="px-6 py-3">Unit Price</th>
                                <th class="px-6 py-3">Qty</th>
                                <th class="px-6 py-3">Subtotal</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse($cartItems as $index => $item)
                                <tr>
                                    <td class="px-6 py-3.5">
                                        <div class="font-bold text-slate-900">{{ $item['name'] }}</div>
                                        <div class="text-xs text-slate-500 font-mono">{{ $item['sku'] }}</div>
                                    </td>
                                    <td class="px-6 py-3.5">₱{{ number_format($item['unit_price'], 2) }}</td>
                                    <td class="px-6 py-3.5 font-bold">{{ $item['quantity'] }}</td>
                                    <td class="px-6 py-3.5 font-bold text-slate-900">₱{{ number_format($item['subtotal'], 2) }}</td>
                                    <td class="px-6 py-3.5 text-right">
                                        <button type="button" wire:click="removeItem({{ $index }})" class="text-rose-600 hover:text-rose-800 text-xs font-bold">Remove</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-xs">
                                        No items added yet. Select a product above and click "+ Add Line".
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between sm:p-6">
                    <span class="text-sm font-bold text-slate-600">Grand Total Amount</span>
                    <span class="text-2xl font-black text-slate-900">₱{{ number_format($this->grandTotal, 2) }}</span>
                </div>
            </div>

        </div>

        <!-- Right Col: Customer & Delivery Details Form -->
        <div class="space-y-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm space-y-4 sm:p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Customer & Delivery Info</h3>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Customer Name *</label>
                    <input type="text" wire:model="customer_name" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white" placeholder="Walk-in Customer / Juan Cruz">
                    @error('customer_name') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Phone Number *</label>
                        <input type="text" wire:model="customer_phone" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white" placeholder="0917-000-0000">
                        @error('customer_phone') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Delivery Method</label>
                        <select wire:model.live="delivery_method" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500 focus:bg-white">
                            <option value="shipping">Shipping Courier</option>
                            <option value="meetup">Meetup Location</option>
                        </select>
                    </div>
                </div>

                @if($delivery_method === 'shipping')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Shipping Address *</label>
                        <textarea wire:model="shipping_address" rows="3" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white" placeholder="Full delivery address with landmark..."></textarea>
                        @error('shipping_address') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Meetup Location *</label>
                            <input type="text" wire:model="meetup_location" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white" placeholder="SM Megamall, Trinoma Entrance...">
                            @error('meetup_location') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Meetup Date *</label>
                            <input type="date" wire:model="meetup_date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white">
                            @error('meetup_date') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                <div class="border-t border-slate-100 pt-4 space-y-3">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Payment & Status</h3>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Payment Method</label>
                            <select wire:model="payment_method" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500">
                                <option value="Cash">Cash</option>
                                <option value="GCash">GCash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Maya">Maya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Payment Status</label>
                            <select wire:model="payment_status" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500">
                                <option value="paid">Paid</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Initial Order Status</label>
                        <select wire:model="order_status" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500">
                            <option value="completed">Completed (Immediate Sale)</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="packed">Packed</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4">
                    @error('cartItems') <p class="mb-2 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    <button
                        type="button"
                        @click="if (!$wire.customer_name.trim() || !$wire.customer_phone.trim() || !$wire.cartItems.length || ($wire.delivery_method === 'shipping' && !$wire.shipping_address.trim()) || ($wire.delivery_method === 'meetup' && (!$wire.meetup_location.trim() || !$wire.meetup_date))) { $wire.saveOrder(); return; } if (!navigator.onLine) { window.AlasOffline.queue('sale', { customer_name: $wire.customer_name, customer_phone: $wire.customer_phone, customer_email: $wire.customer_email, delivery_method: $wire.delivery_method, shipping_address: $wire.shipping_address, meetup_date: $wire.meetup_date, meetup_location: $wire.meetup_location, payment_method: $wire.payment_method, payment_status: $wire.payment_status, order_status: $wire.order_status, notes: $wire.notes, items: @js($cartItems) }); } else { $wire.saveOrder(); }"
                        wire:loading.attr="disabled"
                        wire:target="saveOrder"
                        class="min-h-[48px] w-full px-4 py-3 bg-amber-500 hover:bg-amber-600 disabled:cursor-wait disabled:opacity-60 text-slate-950 font-bold text-sm rounded-xl shadow-md transition-colors"
                    >
                        <span wire:loading.remove wire:target="saveOrder">Save & Create Order</span>
                        <span wire:loading wire:target="saveOrder" class="inline-flex items-center gap-2"><svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="8" stroke="currentColor" stroke-width="3"/><path class="opacity-90" fill="currentColor" d="M12 4a8 8 0 0 1 8 8h-3a5 5 0 0 0-5-5V4z"/></svg>Saving order…</span>
                    </button>
                </div>
            </div>
        </div>

    </div>

</div>
