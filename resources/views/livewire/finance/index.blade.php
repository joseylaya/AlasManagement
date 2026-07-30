<div class="space-y-6">

    @if($canAccessFinance)
    <!-- Financial KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Current Cash Balance -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Current Total Cash</span>
            <span class="text-3xl font-black text-slate-900 tracking-tight mt-2 block">₱{{ number_format($currentCash, 2) }}</span>
            <p class="text-xs text-slate-500 mt-1">Sum of all master cash movements</p>
        </div>

        <!-- Available Business Funds -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm border-l-4 border-l-amber-500">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Available Business Funds</span>
            <span class="text-3xl font-black text-amber-600 tracking-tight mt-2 block">₱{{ number_format($availableFunds, 2) }}</span>
            <p class="text-xs text-slate-500 mt-1">Funds available after obligations</p>
        </div>

        <!-- Today Inflow / Outflow -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Today Cash Movement</span>
            <div class="mt-2 space-y-1">
                <div class="text-sm font-bold text-emerald-600">+₱{{ number_format($todayIncome, 2) }} (Inflow)</div>
                <div class="text-sm font-bold text-rose-600">-₱{{ number_format($todayExpenses, 2) }} (Expenses)</div>
            </div>
        </div>

        <!-- Monthly Operating Profit -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Monthly Net Profit</span>
            <span class="text-3xl font-black text-slate-900 tracking-tight mt-2 block">₱{{ number_format($monthlyProfit, 2) }}</span>
            <p class="text-xs text-slate-500 mt-1">Revenue - COGS - Expenses</p>
        </div>
    </div>
    @else
    <div class="bg-white border border-[#E8E8E8] rounded-2xl p-5 shadow-sm">
        <h2 class="text-[15px] font-bold text-[#111111]">Finance ledger</h2>
        <p class="text-[13px] text-[#666666] mt-1">You have read-only access to permitted transactions. Cash balances and financial reports are restricted to Managers and the Owner.</p>
    </div>
    @endif

    <!-- Header Actions & Cash Transactions Table -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Cash Transactions Master Ledger</h2>
                <p class="text-xs text-slate-500">Single source of truth for all business cash inflows and outflows</p>
            </div>

            @if($canModifyFinance)
            <div class="flex items-center space-x-3">
                <button type="button"
                    wire:click="$set('showExpenseModal', true)" 
                    class="px-4 py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-bold text-sm rounded-xl shadow-sm transition-colors flex items-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Record Expense
                </button>

                @if($canRecordWithdrawals)
                <button
                    type="button"
                    @click="$event.stopImmediatePropagation(); if (!navigator.onLine) { window.AlasOffline.queueWithdrawal(); } else { $wire.set('showDrawalModal', true); }"
                    class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-sm rounded-xl shadow-sm transition-colors flex items-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Owner Withdrawal
                </button>
                @endif
            </div>
            @endif
        </div>

        <!-- Filter Bar -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search transaction # or description..." 
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white"
            >

            <select wire:model.live="selectedType" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white">
                <option value="">All Transaction Types</option>
                <option value="sale">Sale Income (+)</option>
                <option value="expense">Operating Expense (-)</option>
                <option value="owner_withdrawal">Owner Withdrawal (-)</option>
                <option value="refund">Refund (-)</option>
            </select>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="md:hidden space-y-3">
        @forelse($cashTransactions as $tx)
            <article class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="text-[11px] font-mono font-bold text-slate-500">{{ $tx->transaction_number }}</p><p class="mt-1 text-[13px] font-semibold text-slate-900">{{ $tx->description }}</p></div>
                    <p class="text-[15px] font-black {{ $tx->amount >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $tx->amount >= 0 ? '+' : '−' }}₱{{ number_format(abs($tx->amount), 2) }}</p>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-[11px] text-slate-600"><span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold">{{ str_replace('_', ' ', $tx->type) }}</span><span>{{ $tx->transaction_date?->format('M j, Y') }}</span><span>Recorded by {{ $tx->user->name ?? 'System' }}</span></div>
            </article>
        @empty
            <div class="rounded-2xl bg-white p-8 text-center text-sm text-slate-500">No cash transactions recorded.</div>
        @endforelse
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-3.5">CTX #</th>
                        <th class="px-6 py-3.5">Type</th>
                        <th class="px-6 py-3.5">Description</th>
                        <th class="px-6 py-3.5">Recorded By</th>
                        <th class="px-6 py-3.5">Date & Time</th>
                        <th class="px-6 py-3.5 text-right">Cash Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($cashTransactions as $tx)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-slate-900 text-xs">
                                {{ $tx->transaction_number }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $typeStyles = [
                                        'sale' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'expense' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        'owner_withdrawal' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'refund' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    ];
                                @endphp
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full border uppercase tracking-wider {{ $typeStyles[$tx->type] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ str_replace('_', ' ', $tx->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                {{ $tx->description }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                {{ $tx->user->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500 font-mono">
                                {{ $tx->transaction_date ? $tx->transaction_date->format('M d, Y g:i A') : '' }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-black text-base {{ $tx->amount >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $tx->amount >= 0 ? '+' : '' }}₱{{ number_format($tx->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No cash transactions recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($cashTransactions->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $cashTransactions->links() }}
            </div>
        @endif
    </div>

    <!-- Record Expense Modal -->
    @if($showExpenseModal && $canModifyFinance)
        <div class="fixed inset-0 z-[90] bg-slate-900/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-3 sm:p-4">
            <div class="bg-white rounded-t-2xl sm:rounded-2xl max-w-md w-full max-h-[88vh] overflow-y-auto p-6 shadow-2xl space-y-5 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-900 text-base">💸 Record Business Expense</h3>
                    <button type="button" wire:click="$set('showExpenseModal', false)" class="min-h-[44px] min-w-[44px] text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <form wire:submit.prevent="saveExpense" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Expense Category *</label>
                        <select wire:model="expense_category_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500">
                            @foreach($expenseCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('expense_category_id') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Expense Amount (₱) *</label>
                        <input type="number" step="0.01" wire:model="expense_amount" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500" placeholder="0.00">
                        @error('expense_amount') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Expense Date *</label>
                        <input type="date" wire:model="expense_date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Description / Purpose *</label>
                        <textarea wire:model="expense_description" rows="2" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500" placeholder="Polymailers, courier fees, Facebook ad spend..."></textarea>
                        @error('expense_description') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-2 flex items-center justify-end space-x-3">
                        <button type="button" wire:click="$set('showExpenseModal', false)" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs rounded-xl shadow">Record Expense & Deduct Cash</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Record Owner Withdrawal Modal -->
    @if($showDrawalModal && $canRecordWithdrawals)
        <div class="fixed inset-0 z-[90] bg-slate-900/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-3 sm:p-4">
            <div class="bg-white rounded-t-2xl sm:rounded-2xl max-w-md w-full max-h-[88vh] overflow-y-auto p-6 shadow-2xl space-y-5 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-900 text-base">👑 Record Owner Cash Withdrawal</h3>
                    <button type="button" wire:click="$set('showDrawalModal', false)" class="min-h-[44px] min-w-[44px] text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <div class="bg-amber-50 p-3 rounded-xl border border-amber-200 text-xs text-amber-900">
                    💡 <strong>Constitution Rule:</strong> Owner withdrawals reduce liquid business cash but <strong>do NOT reduce operating profit</strong>.
                </div>

                <form wire:submit.prevent="saveOwnerWithdrawal" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Withdrawal Amount (₱) *</label>
                        <input type="number" step="0.01" wire:model="drawal_amount" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500" placeholder="0.00">
                        @error('drawal_amount') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Withdrawal Date *</label>
                        <input type="date" wire:model="drawal_date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Reason / Note *</label>
                        <input type="text" wire:model="drawal_reason" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500" placeholder="Owner monthly equity drawdown">
                        @error('drawal_reason') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-2 flex items-center justify-end space-x-3">
                        <button type="button" wire:click="$set('showDrawalModal', false)" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-xl shadow">Record Withdrawal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
