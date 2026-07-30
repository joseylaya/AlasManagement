<div class="space-y-6">

    <!-- Header Actions & Filters -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Clothing Catalog</h2>
                <p class="text-xs text-slate-500">Manage apparel products, prices, and variant SKUs</p>
            </div>
            @if(auth()->check() && (auth()->user()->isOwner() || auth()->user()->isManager()))
            <a href="{{ route('products.create') }}" wire:navigate class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-sm rounded-xl shadow-sm transition-colors flex items-center justify-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add New Product
            </a>
            @endif
        </div>

        <!-- Search and Filter Bar -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
            <div class="relative">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Search by product name, SKU..." 
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition-all"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div>
                <select wire:model.live="selectedCategory" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition-all">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="selectedStatus" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition-all">
                    <option value="active">Active Products</option>
                    <option value="inactive">Inactive</option>
                    <option value="archived">Archived</option>
                    <option value="">All Statuses</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Products Table Card -->
    <div class="md:hidden space-y-3">
        @forelse($products as $product)
            @php
                $isLow = $product->inventory && $product->inventory->is_low_stock;
            @endphp
            <article class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3"><div><h3 class="text-[14px] font-bold text-slate-900">{{ $product->product_name }}</h3><p class="mt-1 text-[11px] font-mono text-slate-500">{{ $product->sku }} · {{ $product->color ?? '—' }} / {{ $product->size ?? '—' }}</p></div><p class="text-[14px] font-black text-slate-900">₱{{ number_format($product->selling_price, 2) }}</p></div>
                <div class="mt-3 flex items-center justify-between"><span class="text-[12px] text-slate-600">{{ $product->current_stock }} available</span><span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $isLow ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $isLow ? 'Low stock' : 'In stock' }}</span></div>
                @if(auth()->user()->canManageProducts())<div class="mt-4 flex gap-3"><a href="{{ route('products.edit', $product->id) }}" wire:navigate class="min-h-[44px] inline-flex items-center rounded-xl bg-slate-100 px-3 text-[12px] font-bold text-slate-800">✎ Edit product</a>@if($product->status !== 'archived')<button type="button" wire:click="archiveProduct({{ $product->id }})" wire:confirm="Archive {{ $product->product_name }}?" class="min-h-[44px] px-2 text-[12px] font-bold text-rose-700">⌫ Archive</button>@endif</div>@endif
            </article>
        @empty
            <div class="rounded-2xl bg-white p-8 text-center text-sm text-slate-500">No products found matching your filters.</div>
        @endforelse
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Product Info</th>
                        <th class="px-6 py-3.5">SKU</th>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Variant</th>
                        <th class="px-6 py-3.5">Price / Cost</th>
                        <th class="px-6 py-3.5">Current Stock</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">{{ $product->product_name }}</div>
                                <div class="text-xs text-slate-500">{{ Str::limit($product->description, 50) }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-slate-800 text-xs">
                                {{ $product->sku }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold bg-slate-100 text-slate-700 rounded-md">
                                    {{ $product->category }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-slate-600">
                                {{ $product->color ?? '-' }} / {{ $product->size ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">₱{{ number_format($product->selling_price, 2) }}</div>
                                <div class="text-[11px] text-slate-400">Cost: ₱{{ number_format($product->cost_price, 2) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $stock = $product->current_stock;
                                    $isLow = $product->inventory && $product->inventory->is_low_stock;
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full {{ $isLow ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                    {{ $stock }} units
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                @if(auth()->user()->canManageProducts())
                                <a href="{{ route('products.edit', $product->id) }}" wire:navigate class="text-xs font-bold text-blue-600 hover:text-blue-800">Edit</a>
                                @if($product->status !== 'archived')
                                    <button 
                                        wire:click="archiveProduct({{ $product->id }})" 
                                        wire:confirm="Are you sure you want to archive {{ $product->product_name }}?"
                                        class="text-xs font-bold text-rose-600 hover:text-rose-800"
                                    >
                                        Archive
                                    </button>
                                @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                No products found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $products->links() }}
            </div>
        @endif
    </div>

</div>
