<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900">Add Product</h2>
            <p class="text-xs text-slate-500">Product details, sizes, stock, and pricing</p>
        </div>
        <a href="{{ route('products.index') }}" wire:navigate class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-200">← Back</a>
    </div>

    <form wire:submit.prevent="save" class="space-y-7 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="space-y-4">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Product name *</label>
                <input wire:model="product_name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-amber-500" placeholder="ALAS Heavyweight Tee">
                @error('product_name')<span class="mt-1 block text-xs font-semibold text-rose-500">{{ $message }}</span>@enderror
                <p class="mt-1 text-xs text-slate-400">This is the name customers will see. Internal variant names are generated automatically.</p>
            </div>

            @if($storefrontProducts->isNotEmpty())
                <details class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                    <summary class="cursor-pointer text-xs font-bold text-slate-700">Adding sizes to an existing product?</summary>
                    <div class="mt-3">
                        <select wire:model="storefront_product_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm">
                            <option value="">No — create a new product</option>
                            @foreach($storefrontProducts as $storefrontProduct)
                                <option value="{{ $storefrontProduct->id }}">Yes — add to {{ $storefrontProduct->name }}</option>
                            @endforeach
                        </select>
                        @error('storefront_product_id')<span class="mt-1 block text-xs font-semibold text-rose-500">{{ $message }}</span>@enderror
                    </div>
                </details>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Category *</label>
                    <select wire:model="category" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm">
                        @foreach(['T-Shirts', 'Hoodies', 'Pants', 'Shorts', 'Accessories', 'Outerwear'] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Color</label>
                    <input wire:model="color" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm" placeholder="Washed Black">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Material</label>
                    <input wire:model="material" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm" placeholder="240 GSM Cotton">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Description</label>
                <textarea wire:model="description" rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm" placeholder="Product details, fit, and care instructions"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Image URL</label>
                <input type="url" wire:model="image_url" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm" placeholder="https://...">
                @error('image_url')<span class="mt-1 block text-xs font-semibold text-rose-500">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="space-y-4 border-t border-slate-100 pt-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Sizes and starting stock</h3>
                    <p class="mt-1 text-xs text-slate-400">SKUs are generated automatically.</p>
                </div>
                <button type="button" wire:click="addVariant" class="shrink-0 rounded-xl bg-indigo-50 px-4 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-100">+ Add Size</button>
            </div>
            @error('variants')<span class="block text-xs font-semibold text-rose-500">{{ $message }}</span>@enderror

            <div class="space-y-3">
                @foreach($variants as $index => $variant)
                    <div wire:key="variant-{{ $index }}" class="grid grid-cols-[1fr_1fr_auto] items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Size *</label>
                            <input wire:model="variants.{{ $index }}.size" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold uppercase" placeholder="L">
                            @error("variants.$index.size")<span class="mt-1 block text-xs font-semibold text-rose-500">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-600">Stock *</label>
                            <input type="number" min="0" wire:model="variants.{{ $index }}.initial_stock" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold">
                            @error("variants.$index.initial_stock")<span class="mt-1 block text-xs font-semibold text-rose-500">{{ $message }}</span>@enderror
                        </div>
                        <button type="button" wire:click="removeVariant({{ $index }})" @disabled(count($variants) === 1) class="mt-6 rounded-lg px-3 py-2.5 text-xs font-bold text-rose-600 hover:bg-rose-50 disabled:opacity-30" aria-label="Remove size {{ $index + 1 }}">Remove</button>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-4 border-t border-slate-100 pt-5">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Pricing</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Selling price (₱) *</label>
                    <input type="number" min="0" step="0.01" wire:model="selling_price" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold">
                    @error('selling_price')<span class="mt-1 block text-xs font-semibold text-rose-500">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Cost price (₱) *</label>
                    <input type="number" min="0" step="0.01" wire:model="cost_price" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold">
                    @error('cost_price')<span class="mt-1 block text-xs font-semibold text-rose-500">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('products.index') }}" wire:navigate class="rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-200">Cancel</a>
            <button type="submit" class="rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-600">Create Product</button>
        </div>
    </form>
</div>
