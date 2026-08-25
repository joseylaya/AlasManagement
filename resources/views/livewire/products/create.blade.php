<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900">Add New Clothing Item</h2>
            <p class="text-xs text-slate-500">Add shared product details, size variants, pricing, and initial inventory</p>
        </div>
        <a href="{{ route('products.index') }}" wire:navigate class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors">
            ← Back to Products
        </a>
    </div>

    <form wire:submit.prevent="save" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">

        <div class="space-y-4 rounded-2xl border border-indigo-100 bg-indigo-50/40 p-4">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-700">Storefront grouping</h3>
                <p class="mt-1 text-xs text-slate-500">Link this SKU to an existing customer-facing product, or create a new product group.</p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Existing storefront product</label>
                <select wire:model.live="storefront_product_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">Create a new storefront product</option>
                    @foreach($storefrontProducts as $storefrontProduct)
                        <option value="{{ $storefrontProduct->id }}">{{ $storefrontProduct->name }}</option>
                    @endforeach
                </select>
            </div>
            @if(!$storefront_product_id)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Customer-facing name *</label><input wire:model="storefront_name" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm" placeholder="ALAS Signature Oversized Tee">@error('storefront_name')<span class="mt-1 block text-xs font-semibold text-rose-500">{{ $message }}</span>@enderror</div>
                    <div><label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Slug</label><input wire:model="storefront_slug" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 font-mono text-sm" placeholder="alas-signature-oversized-tee">@error('storefront_slug')<span class="mt-1 block text-xs font-semibold text-rose-500">{{ $message }}</span>@enderror</div>
                    <div><label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Material</label><input wire:model="material" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm" placeholder="240 GSM Premium Cotton"></div>
                    <div class="grid grid-cols-2 items-end gap-3"><label class="flex items-center gap-3 pb-3 text-sm font-semibold text-slate-700"><input type="checkbox" wire:model="is_featured" class="rounded border-slate-300"> Featured</label><label class="grid gap-1 text-xs font-bold uppercase text-slate-700">Visibility<select wire:model="storefront_status" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-normal normal-case"><option value="active">Active</option><option value="inactive">Inactive</option><option value="archived">Archived</option></select></label></div>
                </div>
                <div><label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Storefront description</label><textarea wire:model="storefront_description" rows="3" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm" placeholder="Customer-facing product story and details"></textarea></div>
            @endif
        </div>

        <!-- Product Basic Details -->
        <div class="space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Product Identification</h3>
            
            <div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Product Name *</label>
                    <input type="text" wire:model="product_name" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white" placeholder="ALAS Heavyweight Tee - Black">
                    @error('product_name') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Category *</label>
                    <select wire:model="category" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white">
                        <option value="T-Shirts">T-Shirts</option>
                        <option value="Hoodies">Hoodies</option>
                        <option value="Pants">Pants</option>
                        <option value="Shorts">Shorts</option>
                        <option value="Accessories">Accessories</option>
                        <option value="Outerwear">Outerwear</option>
                    </select>
                    @error('category') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Color</label>
                    <input type="text" wire:model="color" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white" placeholder="Washed Black">
                </div>

            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Description</label>
                <textarea wire:model="description" rows="3" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white" placeholder="Fabric details, GSM specs, fit instructions..."></textarea>
            </div>
            <div><label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Primary image URL</label><input type="url" wire:model="image_url" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm" placeholder="https://..."><p class="mt-1 text-xs text-slate-400">Used as the storefront fallback until gallery images are configured.</p>@error('image_url')<span class="mt-1 block text-xs font-semibold text-rose-500">{{ $message }}</span>@enderror</div>
        </div>

        <div class="border-t border-slate-100 pt-4 space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Size Variants</h3>
                    <p class="mt-1 text-xs text-slate-500">Each size gets its own SKU and starting stock.</p>
                </div>
                <button type="button" wire:click="addVariant" class="shrink-0 rounded-xl bg-indigo-50 px-4 py-2 text-xs font-bold text-indigo-700 transition-colors hover:bg-indigo-100">+ Add Size</button>
            </div>

            @error('variants') <span class="block text-xs font-semibold text-rose-500">{{ $message }}</span> @enderror

            <div class="space-y-3">
                @foreach($variants as $index => $variant)
                    <div wire:key="variant-{{ $index }}" class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:grid-cols-[1fr_2fr_1fr_auto] sm:items-start">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Size *</label>
                            <input type="text" wire:model="variants.{{ $index }}.size" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold uppercase" placeholder="S">
                            @error("variants.$index.size") <span class="mt-1 block text-xs font-semibold text-rose-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">SKU *</label>
                            <input type="text" wire:model="variants.{{ $index }}.sku" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 font-mono text-sm" placeholder="ALAS-TEE-BLK-S">
                            @error("variants.$index.sku") <span class="mt-1 block text-xs font-semibold text-rose-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Initial Stock *</label>
                            <input type="number" min="0" wire:model="variants.{{ $index }}.initial_stock" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold">
                            @error("variants.$index.initial_stock") <span class="mt-1 block text-xs font-semibold text-rose-500">{{ $message }}</span> @enderror
                        </div>
                        <button type="button" wire:click="removeVariant({{ $index }})" @disabled(count($variants) === 1) class="mt-6 rounded-xl px-3 py-2.5 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-30" aria-label="Remove size {{ $index + 1 }}">Remove</button>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="border-t border-slate-100 pt-4 space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pricing & Margins</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Selling Price (₱) *</label>
                    <input type="number" step="0.01" wire:model="selling_price" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500 focus:bg-white">
                    @error('selling_price') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Cost Price (₱) *</label>
                    <input type="number" step="0.01" wire:model="cost_price" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500 focus:bg-white">
                    @error('cost_price') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-4 space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Stock Threshold</h3>
            <div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Low Stock Alert Threshold *</label>
                    <input type="number" wire:model="min_stock_threshold" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500 focus:bg-white">
                    @error('min_stock_threshold') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="pt-4 flex items-center justify-end space-x-3">
            <a href="{{ route('products.index') }}" wire:navigate class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-xl transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-sm rounded-xl shadow-md transition-colors">
                Save Product
            </button>
        </div>
    </form>
</div>
