<div class="space-y-6">

    <!-- Header Actions & Filters -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Inventory Control</h2>
            <p class="text-xs text-slate-500">Every stock change is audited with mandatory movement logs</p>
        </div>

        <div class="flex items-center space-x-3">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Filter by product name, SKU..." 
                class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white"
            >
            <button 
                wire:click="$toggle('filterLowStock')" 
                class="px-3.5 py-2 text-xs font-bold rounded-xl border transition-colors {{ $filterLowStock ? 'bg-rose-500 text-white border-rose-600' : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200' }}"
            >
                ⚠️ Low Stock Only
            </button>
        </div>
    </div>

    <!-- Inventory Table Card -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Product Name</th>
                        <th class="px-6 py-3.5">SKU</th>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Current Stock</th>
                        <th class="px-6 py-3.5">Min Threshold</th>
                        <th class="px-6 py-3.5">Stock Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($inventories as $inv)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-900">
                                {{ $inv->product->product_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-xs text-slate-700">
                                {{ $inv->product->sku ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                {{ $inv->product->category ?? 'General' }}
                            </td>
                            <td class="px-6 py-4 font-black text-slate-900 text-base">
                                {{ $inv->current_stock }} pcs
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold text-slate-500">
                                {{ $inv->min_stock_threshold }} pcs
                            </td>
                            <td class="px-6 py-4">
                                @if($inv->is_low_stock)
                                    <span class="px-2.5 py-1 text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 rounded-full flex items-center w-fit">
                                        ⚠️ Low Stock
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full flex items-center w-fit">
                                        ✓ In Stock
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button 
                                    wire:click="openAddStockModal({{ $inv->product_id }})" 
                                    class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-bold rounded-lg border border-amber-200 transition-colors"
                                >
                                    + Restock
                                </button>
                                <button 
                                    wire:click="openAdjustStockModal({{ $inv->product_id }})" 
                                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-lg border border-slate-300 transition-colors"
                                >
                                    Adjust
                                </button>
                                <button 
                                    wire:click="viewHistory({{ $inv->product_id }})" 
                                    class="px-2.5 py-1 text-blue-600 hover:text-blue-800 text-xs font-bold"
                                >
                                    History
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                No inventory records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($inventories->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $inventories->links() }}
            </div>
        @endif
    </div>

    <!-- Restock / Adjust Stock Modal -->
    @if($showMovementModal && $selectedProduct)
        <div class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-900 text-base">
                        {{ $movementActionType === 'add' ? '➕ Restock Product' : '✏️ Adjust Inventory Count' }}
                    </h3>
                    <button wire:click="resetModal" class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs space-y-1">
                    <div class="font-bold text-slate-900">{{ $selectedProduct->product_name }}</div>
                    <div class="text-slate-500 font-mono">SKU: {{ $selectedProduct->sku }} | Current Stock: <span class="font-bold text-slate-900">{{ $selectedProduct->current_stock }} pcs</span></div>
                </div>

                <form wire:submit.prevent="processStockMovement" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            {{ $movementActionType === 'add' ? 'Quantity to Add *' : 'New Total Stock Count *' }}
                        </label>
                        <input 
                            type="number" 
                            wire:model="stockQuantity" 
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500 focus:bg-white"
                        >
                        @error('stockQuantity') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Reason for Stock Change *</label>
                        <input 
                            type="text" 
                            wire:model="reason" 
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white"
                            placeholder="Supplier delivery #1042, Physical recount..."
                        >
                        @error('reason') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if($movementActionType === 'add')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Reference Number (Optional)</label>
                        <input 
                            type="text" 
                            wire:model="referenceNumber" 
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white"
                            placeholder="PO-2026-001"
                        >
                    </div>
                    @endif

                    <div class="pt-2 flex items-center justify-end space-x-3">
                        <button type="button" wire:click="resetModal" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow">Confirm & Log Stock Movement</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Stock Movement History Modal -->
    @if($showHistoryModal && $historyProduct)
        <div class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 border border-slate-200 max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-base">Movement Trail — {{ $historyProduct->product_name }}</h3>
                        <p class="text-xs text-slate-500 font-mono">SKU: {{ $historyProduct->sku }}</p>
                    </div>
                    <button wire:click="$set('showHistoryModal', false)" class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <div class="overflow-y-auto flex-1 pr-1">
                    <div class="space-y-3">
                        @forelse($movementsHistory as $m)
                            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-slate-900 capitalize">{{ str_replace('_', ' ', $m->movement_type) }}</div>
                                    <div class="text-slate-600 mt-0.5">{{ $m->reason }}</div>
                                    <div class="text-[10px] text-slate-400 mt-1">By {{ $m->user->name ?? 'System' }} • {{ $m->created_at->format('M d, Y g:i A') }}</div>
                                </div>
                                <div class="text-right font-mono font-black text-sm {{ $m->quantity > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }} pcs
                                </div>
                            </div>
                        @empty
                            <div class="text-xs text-slate-500 py-6 text-center">No stock movement history recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
