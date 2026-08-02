<div class="mx-auto max-w-6xl space-y-5">
    @if(session()->has('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[13px] font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    <section class="flex flex-col gap-4 rounded-3xl bg-[#111111] p-5 text-white shadow-xl sm:flex-row sm:items-end sm:justify-between sm:p-7">
        <div><p class="text-[11px] font-black uppercase tracking-[0.16em] text-amber-400">Dashboard showcase</p><h2 class="mt-1 text-xl font-black">Clothing design gallery</h2><p class="mt-2 max-w-2xl text-[13px] leading-relaxed text-white/65">Upload clothing designs here. Active images rotate in the dashboard carousel for Owners, Managers, and Staff.</p></div>
        <button type="button" wire:click="$set('showUploadModal', true)" class="min-h-[46px] shrink-0 rounded-xl bg-amber-400 px-5 text-[13px] font-black text-[#111111] transition hover:bg-amber-300">＋ Upload gallery image</button>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($banners as $banner)
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <img src="{{ asset('storage/'.$banner->image_path) }}" alt="{{ $banner->title ?: 'Dashboard gallery image' }}" class="aspect-[16/9] w-full object-cover">
                <div class="p-4"><div class="flex items-start justify-between gap-3"><div><h3 class="text-[14px] font-bold text-slate-900">{{ $banner->title ?: 'Untitled design' }}</h3><p class="mt-1 text-[11px] text-slate-500">Uploaded by {{ $banner->uploader->name }}</p></div><span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $banner->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $banner->is_active ? 'Live' : 'Hidden' }}</span></div><div class="mt-4 flex gap-2"><button type="button" wire:click="toggle({{ $banner->id }})" class="min-h-[38px] rounded-lg border border-slate-200 px-3 text-[11px] font-bold text-slate-700 hover:bg-slate-50">{{ $banner->is_active ? 'Hide' : 'Show' }}</button><button type="button" wire:click="delete({{ $banner->id }})" wire:confirm="Remove this gallery image?" class="min-h-[38px] rounded-lg px-3 text-[11px] font-bold text-rose-600 hover:bg-rose-50">Remove</button></div></div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-16 text-center text-[13px] text-slate-500">No gallery images yet. Upload the first clothing design to show it on every dashboard.</div>
        @endforelse
    </section>

    @if($showUploadModal)
        <div class="fixed inset-0 z-[70] flex items-end bg-black/50 sm:items-center sm:justify-center sm:p-5" role="dialog" aria-modal="true"><div class="app-modal-sheet w-full max-w-lg rounded-t-3xl bg-white p-5 shadow-2xl sm:rounded-3xl sm:p-6"><div class="flex items-center justify-between"><div><h3 class="text-[17px] font-black text-slate-900">Upload gallery image</h3><p class="mt-1 text-[12px] text-slate-500">Visible to every role on the dashboard.</p></div><button type="button" wire:click="$set('showUploadModal', false)" class="min-h-[40px] min-w-[40px] rounded-xl text-slate-500 hover:bg-slate-100">×</button></div><form wire:submit="save" class="mt-5 space-y-4"><div><label class="text-[12px] font-bold text-slate-700">Title <span class="font-normal text-slate-400">(optional)</span></label><input wire:model="title" maxlength="120" placeholder="Example: Summer collection" class="mt-1.5 w-full rounded-xl border-slate-200 px-3.5 py-3 text-[14px]"></div><div><label class="text-[12px] font-bold text-slate-700">Design image</label><input type="file" wire:model="image" accept="image/*" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-[12px] text-slate-600">@if($image)<img src="{{ $image->temporaryUrl() }}" alt="Preview" class="mt-3 max-h-56 w-full rounded-xl object-cover">@endif @error('image')<p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>@enderror</div><div class="flex justify-end gap-2"><button type="button" wire:click="$set('showUploadModal', false)" class="min-h-[44px] rounded-xl px-4 text-[13px] font-bold text-slate-600">Cancel</button><button type="submit" wire:loading.attr="disabled" class="min-h-[44px] rounded-xl bg-[#111111] px-5 text-[13px] font-black text-white">Upload image</button></div></form></div></div>
    @endif
</div>
