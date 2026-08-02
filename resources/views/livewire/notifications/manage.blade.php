<div class="mx-auto max-w-5xl space-y-5">
    @if(session()->has('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[13px] font-semibold text-emerald-700"><span class="font-black">✓</span>{{ session('success') }}</div>
    @endif

    <section class="rounded-3xl bg-[#111111] p-5 text-white shadow-xl sm:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-[11px] font-black uppercase tracking-[0.16em] text-amber-400">Team communication</p><h2 class="mt-1 text-xl font-black">Rules, news, and important updates</h2><p class="mt-2 max-w-2xl text-[13px] leading-relaxed text-white/65">Send a visible in-app notification now, or schedule it for later. Uploaded announcement designs also appear in the dashboard carousel for the selected recipients.</p></div>
            <button type="button" wire:click="$set('showComposeModal', true)" class="min-h-[46px] rounded-xl bg-amber-400 px-5 text-[13px] font-black text-[#111111] shadow-lg transition hover:bg-amber-300">＋ New announcement</button>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-[14px] font-bold text-slate-900">Announcement history</h3></div>
        <div class="divide-y divide-slate-100">
            @forelse($announcements as $announcement)
                <article class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div><div class="flex flex-wrap items-center gap-2"><h4 class="text-[14px] font-bold text-slate-900">{{ $announcement->title }}</h4><span class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase {{ $announcement->status === 'sent' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $announcement->status }}</span></div><p class="mt-1 max-w-2xl text-[12px] text-slate-600">{{ $announcement->message }}</p><p class="mt-2 text-[11px] text-slate-400">To: <span class="font-semibold text-slate-600">{{ $announcement->target_role === 'all' ? 'Everyone' : ucfirst($announcement->target_role).' role' }}</span> · By {{ $announcement->creator->name }} · {{ $announcement->sent_at ? 'Sent '.$announcement->sent_at->format('M j, Y g:i A') : 'Scheduled '.$announcement->scheduled_for?->format('M j, Y g:i A') }}</p></div>
                    <span class="shrink-0 text-[11px] font-bold text-slate-500">{{ $announcement->sent_at ? $announcement->recipient_count.' recipients' : 'Waiting to send' }}</span>
                </article>
            @empty
                <p class="px-5 py-14 text-center text-[13px] text-slate-500">No announcements yet.</p>
            @endforelse
        </div>
        @if($announcements->hasPages())<div class="border-t border-slate-100 p-4">{{ $announcements->links() }}</div>@endif
    </section>

    @if($showComposeModal)
        <div class="fixed inset-0 z-[70] flex items-end bg-black/50 p-0 sm:items-center sm:justify-center sm:p-5" role="dialog" aria-modal="true">
            <div class="app-modal-sheet w-full max-w-xl rounded-t-3xl bg-white p-5 shadow-2xl sm:rounded-3xl sm:p-6">
                <div class="flex items-center justify-between"><div><h3 class="text-[17px] font-black text-slate-900">New announcement</h3><p class="mt-1 text-[12px] text-slate-500">Use this for a rule, news item, or important team update.</p></div><button type="button" wire:click="$set('showComposeModal', false)" class="min-h-[40px] min-w-[40px] rounded-xl text-slate-500 hover:bg-slate-100">✕</button></div>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <div><label class="text-[12px] font-bold text-slate-700">Title</label><input wire:model="title" maxlength="120" placeholder="Example: New attendance rule" class="mt-1.5 w-full rounded-xl border-slate-200 px-3.5 py-3 text-[14px] focus:border-amber-500 focus:ring-amber-500">@error('title')<p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-[12px] font-bold text-slate-700">Message</label><textarea wire:model="message" rows="5" maxlength="2000" placeholder="Write the rule, news, or instructions clearly…" class="mt-1.5 w-full rounded-xl border-slate-200 px-3.5 py-3 text-[14px] focus:border-amber-500 focus:ring-amber-500"></textarea>@error('message')<p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-[12px] font-bold text-slate-700">Design / banner image <span class="font-normal text-slate-400">(optional, JPG/PNG/WebP, up to 5 MB)</span></label><p class="mt-1 text-[11px] text-slate-500">When sent, this image is shown in the dashboard carousel for the selected roles.</p><input type="file" wire:model="image" accept="image/*" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-[12px] text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-[11px] file:font-bold file:text-white">@if($image)<img src="{{ $image->temporaryUrl() }}" alt="Announcement image preview" class="mt-3 max-h-48 w-full rounded-xl object-cover">@endif @error('image')<p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-4 sm:grid-cols-2"><div><label class="text-[12px] font-bold text-slate-700">Send to</label><select wire:model="target_role" class="mt-1.5 w-full rounded-xl border-slate-200 px-3.5 py-3 text-[14px]"><option value="all">Everyone</option><option value="owner">Owners</option><option value="manager">Managers</option><option value="staff">Staff / Promoters</option></select></div><div><label class="text-[12px] font-bold text-slate-700">Delivery</label><select wire:model.live="delivery" class="mt-1.5 w-full rounded-xl border-slate-200 px-3.5 py-3 text-[14px]"><option value="immediate">Send now</option><option value="scheduled">Schedule</option></select></div></div>
                    @if($delivery === 'scheduled')<div><label class="text-[12px] font-bold text-slate-700">Send on</label><input type="datetime-local" wire:model="scheduled_for" class="mt-1.5 w-full rounded-xl border-slate-200 px-3.5 py-3 text-[14px]">@error('scheduled_for')<p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>@enderror</div>@endif
                    <div class="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-end"><button type="button" wire:click="$set('showComposeModal', false)" class="min-h-[44px] rounded-xl px-4 text-[13px] font-bold text-slate-600 hover:bg-slate-100">Cancel</button><button type="submit" wire:loading.attr="disabled" class="min-h-[44px] rounded-xl bg-[#111111] px-5 text-[13px] font-black text-white hover:bg-slate-800"><span wire:loading.remove>{{ $delivery === 'scheduled' ? 'Schedule announcement' : 'Send announcement' }}</span><span wire:loading>Saving…</span></button></div>
                </form>
            </div>
        </div>
    @endif
</div>
