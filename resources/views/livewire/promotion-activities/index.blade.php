<div class="mx-auto max-w-5xl space-y-5">
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex sm:items-center sm:justify-between sm:p-6">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Promotion incentives</p>
            <h2 class="mt-1 text-lg font-extrabold text-slate-900">{{ auth()->user()->isStaff() ? 'My activity submissions' : 'Promotion activity review' }}</h2>
            <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ auth()->user()->isStaff() ? 'Submit proof, then follow the review and compensation status here.' : 'Approve verified staff activities to create one finance-ready incentive record.' }}</p>
        </div>
        @if(auth()->user()->isStaff())
            <button type="button" wire:click="$set('showSubmitModal', true)" class="mt-4 inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 text-sm font-bold text-slate-950 shadow-sm hover:bg-amber-600 sm:mt-0 sm:w-auto">
                <span class="text-lg leading-none">＋</span> Submit activity
            </button>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse($activities as $activity)
                @php
                    $status = [
                        'submitted' => 'bg-amber-50 text-amber-800 border-amber-200',
                        'approved' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                        'rejected' => 'bg-rose-50 text-rose-800 border-rose-200',
                    ][$activity->status] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                @endphp
                <article x-data="{ proofOpen: false }" class="space-y-3 p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-900">{{ $activity->activity_type }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                @if(!auth()->user()->isStaff()) {{ $activity->user->name }} · @endif
                                {{ $activity->activity_date->format('M j, Y') }}
                                @if($activity->campaign) · {{ $activity->campaign }} @endif
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $status }}">{{ str_replace('_', ' ', $activity->status) }}</span>
                    </div>

                    @if($activity->platform || $activity->outcome)
                        <div class="rounded-xl bg-slate-50 p-3 text-xs leading-relaxed text-slate-600">
                            @if($activity->platform)<p><span class="font-bold text-slate-700">Platform:</span> {{ $activity->platform }}</p>@endif
                            @if($activity->outcome)<p class="{{ $activity->platform ? 'mt-1' : '' }} break-words">{{ $activity->outcome }}</p>@endif
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        @if($activity->proof_path && $activity->proof_status === 'active')
                            <button type="button" @click="proofOpen = true" class="inline-flex min-h-[36px] items-center rounded-lg border border-slate-200 px-3 font-bold text-slate-700 hover:bg-slate-50">View proof</button>
                            <template x-teleport="body">
                                <div x-show="proofOpen" x-cloak @keydown.escape.window="proofOpen = false" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/70 p-4" style="display: none">
                                    <button type="button" @click="proofOpen = false" class="absolute inset-0 cursor-default" aria-label="Close proof preview"></button>
                                    <section @click.stop class="relative max-h-[92dvh] w-full max-w-3xl overflow-auto rounded-2xl bg-white p-3 shadow-2xl sm:p-4" role="dialog" aria-modal="true" aria-label="Promotion activity proof">
                                        <div class="mb-3 flex items-center justify-between gap-3"><p class="truncate text-xs font-bold text-slate-700">{{ $activity->proof_original_name ?? 'Activity proof' }}</p><button type="button" @click="proofOpen = false" class="flex min-h-[40px] min-w-[40px] items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100" aria-label="Close proof preview">✕</button></div>
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($activity->proof_path) }}" alt="Proof for {{ $activity->activity_type }}" class="mx-auto max-h-[78dvh] w-auto max-w-full rounded-xl object-contain">
                                    </section>
                                </div>
                            </template>
                        @elseif($activity->proof_status === 'purged')
                            <span class="text-slate-400">Proof removed under retention policy{{ $activity->proof_purged_at ? ' on '.$activity->proof_purged_at->format('M j, Y') : '' }}.</span>
                        @else
                            <span class="text-slate-400">No proof attached</span>
                        @endif

                        @if($activity->approved_amount && (!$activity->compensationRecord || in_array($activity->compensationRecord->status, ['payable', 'paid'], true)))
                            <span class="rounded-lg bg-emerald-50 px-3 py-2 font-bold text-emerald-800">₱{{ number_format($activity->approved_amount, 2) }} incentive</span>
                        @endif

                        @if($activity->compensationRecord)
                            <span class="rounded-lg bg-slate-100 px-3 py-2 font-semibold text-slate-600">{{ $activity->compensationRecord->record_number }} · {{ str_replace('_', ' ', $activity->compensationRecord->status) }}</span>
                        @endif
                    </div>

                    @if($activity->review_notes)
                        <p class="border-l-2 border-slate-200 pl-3 text-xs leading-relaxed text-slate-600"><span class="font-bold text-slate-700">Review:</span> {{ $activity->review_notes }}</p>
                    @endif

                    @if($canReview && $activity->status === 'submitted' && $activity->user_id !== auth()->id())
                        <button type="button" wire:click="openReview({{ $activity->id }})" class="min-h-[40px] rounded-xl bg-slate-900 px-4 text-xs font-bold text-white hover:bg-slate-700">Review activity</button>
                    @endif
                </article>
            @empty
                <div class="px-6 py-14 text-center text-sm text-slate-500">No promotion activities yet.</div>
            @endforelse
        </div>

        @if($activities->hasPages())
            <div class="border-t border-slate-100 p-4">{{ $activities->links() }}</div>
        @endif
    </section>

    @if($showSubmitModal)
        <template x-teleport="body">
            <div wire:click.self="$set('showSubmitModal', false)" class="fixed inset-0 z-[90] flex items-end bg-black/30 sm:items-center sm:justify-center sm:p-4">
                <section class="app-modal-sheet max-h-[92dvh] w-full overflow-y-auto rounded-t-2xl bg-white p-4 shadow-2xl sm:max-h-[88vh] sm:max-w-lg sm:rounded-2xl sm:p-6">
                    <div class="mb-5 flex items-center justify-between"><div><h3 class="text-base font-extrabold text-slate-900">Submit promotion activity</h3><p class="mt-1 text-xs text-slate-500">Complete every field and attach a screenshot or photo.</p></div><button type="button" wire:click="$set('showSubmitModal', false)" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100" aria-label="Close">✕</button></div>
                    <form wire:submit="submit" class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2"><div><label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-600">Activity</label><select wire:model="activity_type" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"><option>Event / booth shift</option><option>Livestream</option><option>Content post</option><option>Referral</option><option>Product demonstration</option><option>Other promotion</option></select>@error('activity_type')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-600">Date</label><input type="date" wire:model="activity_date" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm">@error('activity_date')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror</div></div>
                        <div class="grid gap-3 sm:grid-cols-2"><div><input type="text" wire:model="campaign" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm" placeholder="Campaign or event"><p>@error('campaign')<span class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror</p></div><div><input type="text" wire:model="platform" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm" placeholder="Platform / location"><p>@error('platform')<span class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror</p></div></div>
                        <div><textarea wire:model="outcome" rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm" placeholder="What happened? Add outcome, leads, reach, or referral details."></textarea>@error('outcome')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-600">Proof image (required, max 5 MB)</label><input type="file" wire:model="proof" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-bold file:text-slate-700"><div wire:loading wire:target="proof" class="mt-2 text-xs font-semibold text-amber-700">Uploading proof…</div>@error('proof')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror</div>
                        <button type="submit" wire:loading.attr="disabled" wire:target="submit,proof" class="min-h-[48px] w-full rounded-xl bg-amber-500 text-sm font-bold text-slate-950 disabled:cursor-wait disabled:opacity-60"><span wire:loading.remove wire:target="submit">Submit for review</span><span wire:loading wire:target="submit">Submitting…</span></button>
                    </form>
                </section>
            </div>
        </template>
    @endif

    @if($reviewingActivityId)
        <template x-teleport="body">
            <div wire:click.self="$set('reviewingActivityId', null)" class="fixed inset-0 z-[90] flex items-end bg-black/30 sm:items-center sm:justify-center sm:p-4">
                <section class="app-modal-sheet w-full rounded-t-2xl bg-white p-4 shadow-2xl sm:max-w-md sm:rounded-2xl sm:p-6">
                    <h3 class="text-base font-extrabold text-slate-900">Review activity incentive</h3><p class="mt-1 text-xs text-slate-500">Approval creates one pending compensation record for Owner approval.</p>
                    <div class="mt-5 space-y-4"><div><label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-600">Approved amount</label><input type="number" step="0.01" min="0.01" wire:model="review_amount" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></div><textarea wire:model="review_notes" rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm" placeholder="Review note or rejection reason"></textarea></div>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><button type="button" wire:click="reject" wire:loading.attr="disabled" class="min-h-[44px] rounded-xl border border-rose-200 bg-rose-50 px-4 text-xs font-bold text-rose-700">Reject</button><button type="button" wire:click="approve" wire:loading.attr="disabled" class="min-h-[44px] rounded-xl bg-slate-900 px-4 text-xs font-bold text-white">Approve & create incentive</button></div>
                </section>
            </div>
        </template>
    @endif
</div>
