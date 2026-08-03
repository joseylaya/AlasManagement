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
        <div class="bg-white border border-emerald-200 rounded-2xl p-6 shadow-sm"><span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Capital Added This Month</span><span class="text-3xl font-black text-emerald-700 mt-2 block">₱{{ number_format($capitalAddedThisMonth, 2) }}</span><p class="text-xs text-slate-500 mt-1">Total contributed capital: ₱{{ number_format($totalOwnerCapital, 2) }}</p></div>
        <div class="bg-white border border-violet-200 rounded-2xl p-6 shadow-sm"><span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Net Owner Capital Position</span><span class="text-3xl font-black text-violet-700 mt-2 block">₱{{ number_format($totalOwnerCapital - $ownerWithdrawalsThisMonth, 2) }}</span><p class="text-xs text-slate-500 mt-1">Withdrawals this month: ₱{{ number_format($ownerWithdrawalsThisMonth, 2) }}</p></div>

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
        <p class="text-[13px] text-[#666666] mt-1">You have read-only access to permitted transactions. Some balances and reports have limited access.</p>
    </div>
    @endif

    @if($canViewCompensation)
    <section id="compensation-approvals" class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm scroll-mt-20">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-[15px] font-bold text-slate-900">Salary & Compensation</h2><p class="text-[12px] text-slate-500">₱{{ number_format($compensationCommitments, 2) }} approved and unpaid — reserved from available funds.</p></div>@if(auth()->user()->isOwner() || auth()->user()->isManager())<div class="flex flex-col gap-2 sm:flex-row"><a href="{{ route('promotion-activities.index') }}" wire:navigate class="min-h-[44px] inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 text-[12px] font-bold text-slate-700 hover:bg-slate-50">Review activities</a><button type="button" wire:click="$set('showCompensationModal', true)" class="min-h-[44px] inline-flex items-center justify-center gap-2 rounded-xl bg-[#111111] px-4 text-[12px] font-bold text-white">＋ Add compensation</button></div>@endif</div>
        <div class="mt-4 space-y-2">
        @forelse($compensationRecords as $record)
            <div id="compensation-{{ $record->id }}" class="flex flex-wrap items-center justify-between gap-3 rounded-xl px-4 py-3 {{ $highlightCompensationId === $record->id ? 'bg-amber-100 ring-2 ring-amber-400' : 'bg-slate-50' }}"><div><p class="text-[13px] font-bold text-slate-900">{{ $record->user->name }} <span class="font-normal text-slate-500">· {{ str_replace('_',' ',$record->type) }}</span></p><p class="text-[11px] text-slate-500">{{ $record->record_number }} @if($record->period_start) · {{ $record->period_start->format('M j') }}–{{ $record->period_end?->format('M j, Y') }} @endif</p></div><div class="flex items-center gap-2"><span class="text-[13px] font-black text-slate-900">₱{{ number_format($record->amount,2) }}</span><span class="rounded-full bg-white px-2 py-1 text-[10px] font-bold text-slate-600">{{ str_replace('_',' ',$record->status) }}</span>@if(auth()->user()->isOwner() && $record->status === 'pending_approval')<button type="button" wire:click="approveCompensation({{ $record->id }})" wire:loading.attr="disabled" class="min-h-[36px] rounded-lg bg-blue-600 px-3 text-[11px] font-bold text-white">Approve</button>@elseif(auth()->user()->isOwner() && $record->status === 'payable')<button type="button" wire:click="payCompensation({{ $record->id }})" wire:loading.attr="disabled" class="min-h-[36px] rounded-lg bg-emerald-600 px-3 text-[11px] font-bold text-white">Pay</button>@endif</div></div>
        @empty <p class="py-5 text-center text-[12px] text-slate-500">No salary or incentive records yet.</p>
        @endforelse
        </div>
    </section>
    @endif

    <!-- Header Actions & Cash Transactions Table -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Cash Transactions Master Ledger</h2>
                <p class="text-xs text-slate-500">Single source of truth for all business cash inflows and outflows</p>
            </div>

            @if($canModifyFinance)
            <div class="flex flex-col sm:flex-row gap-3">
                <button type="button"
                    wire:click="$set('showExpenseModal', true)" 
                    class="px-4 py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-bold text-sm rounded-xl shadow-sm transition-colors flex items-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Record Expense
                </button>

                @if(auth()->user()->isOwner())
                <button type="button" wire:click="$set('showCapitalModal', true)" class="min-h-[44px] px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow-sm">＋ Add Funds</button>
                @endif

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
                <option value="capital_injection">Capital Added (+)</option>
                <option value="expense">Operating Expense (-)</option>
                <option value="owner_withdrawal">Owner Withdrawal (-)</option>
                <option value="refund">Refund (-)</option>
            </select>
        </div>
    </div>

    @if($canAccessFinance)
    <section class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm"><div class="mb-3 flex items-center justify-between"><div><h2 class="font-bold text-slate-900">Owner Capital History</h2><p class="text-xs text-slate-500">Capital is financing activity, not sales or profit.</p></div></div><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@forelse($capitalInjections as $capital)<article class="rounded-xl border border-slate-200 p-4"><p class="text-xs font-mono font-bold text-slate-500">{{ $capital->capital_injection_number }}</p><p class="mt-1 text-xl font-black text-emerald-700">₱{{ number_format($capital->amount,2) }}</p><p class="mt-1 text-xs text-slate-600">Capital Added · {{ $capital->account->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $capital->contribution_date->format('M j, Y') }} · {{ ucfirst($capital->status) }}</p>@if(auth()->user()->isOwner() && $capital->status==='posted')<button wire:click="$set('reversingCapitalId',{{ $capital->id }})" class="mt-3 min-h-[38px] rounded-lg border border-rose-300 px-3 text-xs font-bold text-rose-700">Reverse</button>@endif</article>@empty <p class="text-sm text-slate-500">No capital injections recorded.</p>@endforelse</div></section>
    @endif

    @if($showCapitalModal && auth()->user()->isOwner())
    <div class="fixed inset-0 z-[90] overflow-y-auto bg-black/40 p-3 sm:p-6"><section class="mx-auto my-4 w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl"><div class="mb-4 flex justify-between"><div><h3 class="text-lg font-extrabold">Add Funds</h3><p class="text-xs text-slate-500">Owner Capital Injection — not sales or profit.</p></div><button wire:click="$set('showCapitalModal',false)" class="text-xl">✕</button></div>
        <form wire:submit.prevent="reviewCapitalInjection" class="space-y-3"><div><label class="text-xs font-bold">Amount (₱) *</label><input type="number" step="0.01" wire:model="capital_amount" class="mt-1 w-full rounded-xl border p-3 text-lg font-bold" placeholder="0.00">@error('capital_amount')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</div><div><label class="text-xs font-bold">Destination Account *</label><select wire:model="capital_financial_account_id" class="mt-1 w-full rounded-xl border p-3"><option value="">Select account</option>@foreach($financialAccounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></div><div class="grid gap-3 sm:grid-cols-2"><div><label class="text-xs font-bold">Contribution Date *</label><input type="date" wire:model="capital_date" class="mt-1 w-full rounded-xl border p-3"></div><div><label class="text-xs font-bold">Funding Source *</label><input wire:model="capital_funding_source" class="mt-1 w-full rounded-xl border p-3" placeholder="Owner Personal Bank Account"></div></div><div><label class="text-xs font-bold">Reference Number</label><input wire:model="capital_reference_number" class="mt-1 w-full rounded-xl border p-3"></div><div><label class="text-xs font-bold">Description</label><textarea wire:model="capital_description" class="mt-1 w-full rounded-xl border p-3"></textarea></div><div><label class="text-xs font-bold">Proof of Transfer (optional)</label><input type="file" wire:model="capital_proof" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full text-sm">@error('capital_proof')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</div><div><label class="text-xs font-bold">Remarks</label><textarea wire:model="capital_remarks" class="mt-1 w-full rounded-xl border p-3"></textarea></div><button class="sticky bottom-0 min-h-[48px] w-full rounded-xl bg-emerald-600 font-bold text-white" wire:loading.attr="disabled" wire:target="reviewCapitalInjection,capital_proof">Review Capital Injection</button></form>
    </section></div>
    @endif
    @if($showCapitalConfirmation && auth()->user()->isOwner())<div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"><section class="w-full max-w-md rounded-2xl bg-white p-6"><h3 class="text-lg font-extrabold">Add ₱{{ number_format((float)$capital_amount,2) }}?</h3><p class="mt-3 text-sm text-slate-600">This will increase recorded business cash and Owner Contributed Capital. It will not be counted as sales or profit.</p><div class="mt-5 flex gap-3"><button wire:click="$set('showCapitalConfirmation',false)" class="flex-1 rounded-xl border p-3 font-bold">Cancel</button><button wire:click="saveCapitalInjection" wire:loading.attr="disabled" wire:target="saveCapitalInjection" class="flex-1 rounded-xl bg-emerald-600 p-3 font-bold text-white"><span wire:loading.remove wire:target="saveCapitalInjection">Confirm Capital Injection</span><span wire:loading wire:target="saveCapitalInjection">Recording Funds…</span></button></div></section></div>@endif
    @if($reversingCapitalId)<div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"><section class="w-full max-w-md rounded-2xl bg-white p-6"><h3 class="text-lg font-extrabold">Reverse Capital Injection</h3><p class="mt-2 text-sm text-slate-600">The original entry remains in history and a reversing cash transaction will be created.</p><textarea wire:model="capital_reversal_reason" class="mt-4 w-full rounded-xl border p-3" placeholder="Reason for reversal (required)"></textarea><label class="mt-3 flex gap-2 text-xs"><input type="checkbox" wire:model="capital_reversal_override"> I authorize a negative account balance if necessary.</label><div class="mt-4 flex gap-3"><button wire:click="$set('reversingCapitalId',null)" class="flex-1 rounded-xl border p-3 font-bold">Cancel</button><button wire:click="reverseCapitalInjection({{ $reversingCapitalId }})" wire:loading.attr="disabled" class="flex-1 rounded-xl bg-rose-600 p-3 font-bold text-white">Reverse</button></div></section></div>@endif

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
        <template x-teleport="body">
        <div wire:click.self="$set('showExpenseModal', false)" class="fixed inset-0 z-[90] bg-black/30 flex items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="app-modal-sheet bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-md max-h-[92dvh] sm:max-h-[88vh] overflow-y-auto p-6 shadow-2xl space-y-5 border border-slate-200">
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
        </template>
    @endif

    <!-- Record Owner Withdrawal Modal -->
    @if($showDrawalModal && $canRecordWithdrawals)
        <template x-teleport="body">
        <div wire:click.self="$set('showDrawalModal', false)" class="fixed inset-0 z-[90] bg-black/30 flex items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="app-modal-sheet bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-md max-h-[92dvh] sm:max-h-[88vh] overflow-y-auto p-6 shadow-2xl space-y-5 border border-slate-200">
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
        </template>
    @endif

    @if($showCompensationModal && ($canModifyFinance || auth()->user()->isOwner()))
    <template x-teleport="body">
    <div wire:click.self="$set('showCompensationModal', false)" class="fixed inset-0 z-[90] flex items-end sm:items-center justify-center bg-black/30 p-0 sm:p-4">
        <div class="app-modal-sheet w-full sm:max-w-md max-h-[92dvh] sm:max-h-[88vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-5 flex items-center justify-between"><h3 class="text-[16px] font-bold text-slate-900">Add compensation</h3><button type="button" wire:click="$set('showCompensationModal', false)" class="min-h-[44px] min-w-[44px] text-slate-500">✕</button></div>
            <form wire:submit.prevent="createCompensation" class="space-y-4"><div><label class="mb-1 block text-[11px] font-bold text-slate-700">EMPLOYEE</label><select wire:model="compensation_user_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">@foreach($compensationUsers as $employee)<option value="{{ $employee->id }}">{{ $employee->name }} · {{ ucfirst($employee->role) }}</option>@endforeach</select></div><div class="grid grid-cols-2 gap-3"><div><label class="mb-1 block text-[11px] font-bold text-slate-700">TYPE</label><select wire:model="compensation_type" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"><option value="salary">Salary</option><option value="activity_incentive">Activity Incentive</option><option value="quota_incentive">Quota Incentive</option><option value="bonus">Bonus</option><option value="adjustment">Adjustment</option></select></div><div><label class="mb-1 block text-[11px] font-bold text-slate-700">AMOUNT</label><input type="number" step="0.01" wire:model="compensation_amount" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm" placeholder="0.00"></div></div><div class="grid grid-cols-2 gap-3"><input type="date" wire:model="compensation_period_start" class="rounded-xl border border-slate-200 px-3 py-3 text-sm"><input type="date" wire:model="compensation_period_end" class="rounded-xl border border-slate-200 px-3 py-3 text-sm"></div><textarea wire:model="compensation_remarks" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm" placeholder="Remarks (optional)"></textarea><button type="submit" wire:loading.attr="disabled" class="min-h-[44px] w-full rounded-xl bg-[#111111] text-sm font-bold text-white">Submit for approval</button></form>
        </div>
    </div>
    </template>
    @endif

</div>
