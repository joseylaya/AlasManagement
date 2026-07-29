<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-base font-bold text-slate-900">System Preferences & Settings</h2>
            <p class="text-xs text-slate-500">Configure business parameters without code changes</p>
        </div>
    </div>

    <!-- Company & Threshold Settings Form -->
    <form wire:submit.prevent="saveSettings" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Business Parameters</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Company Name</label>
                <input type="text" wire:model="company_name" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Low Stock Reorder Threshold</label>
                <input type="number" wire:model="low_stock_threshold" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Currency Symbol</label>
                <input type="text" wire:model="currency_symbol" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Contact Phone</label>
                <input type="text" wire:model="contact_phone" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Contact Email</label>
                <input type="email" wire:model="contact_email" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500">
            </div>
        </div>

        <div class="pt-2 flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-sm rounded-xl shadow transition-colors">
                Save System Settings
            </button>
        </div>
    </form>

    <!-- Expense Categories Management Card -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Expense Categories</h3>

        <form wire:submit.prevent="addCategory" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input type="text" wire:model="newCategoryName" placeholder="New Category Name (e.g. Packaging)" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500">
            <input type="text" wire:model="newCategoryDescription" placeholder="Description..." class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500">
            <button type="submit" class="py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow transition-colors">
                + Add Category
            </button>
        </form>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
            @foreach($categories as $cat)
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                    <div>
                        <div class="font-bold text-slate-900">{{ $cat->name }}</div>
                        <div class="text-[11px] text-slate-500">{{ $cat->description }}</div>
                    </div>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-100 text-emerald-800 uppercase">Active</span>
                </div>
            @endforeach
        </div>
    </div>

</div>
