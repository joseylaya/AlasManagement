<div class="mx-auto max-w-xl space-y-5">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">My account</p>
        <h2 class="mt-1 text-lg font-extrabold text-slate-900">{{ auth()->user()->name }}</h2>
        <p class="mt-1 text-sm text-slate-500">@{{ auth()->user()->username }} · {{ ucfirst(auth()->user()->role) }}</p>
    </section>

    <section x-data="{ showCurrent: false, showNew: false, showConfirmation: false }" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h3 class="text-base font-extrabold text-slate-900">Change password</h3>
        <p class="mt-1 text-xs leading-relaxed text-slate-500">Use at least 8 characters. You will remain signed in on this device.</p>

        @if($successMessage)
            <div role="status" class="mt-4 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-800">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                {{ $successMessage }}
            </div>
        @endif

        <form wire:submit="updatePassword" class="mt-5 space-y-4">
            <div>
                <label for="current_password" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-600">Current password</label>
                <div class="relative"><input :type="showCurrent ? 'text' : 'password'" id="current_password" wire:model="current_password" autocomplete="current-password" class="min-h-[46px] w-full rounded-xl border border-slate-200 bg-slate-50 px-3 pr-11 text-sm outline-none focus:border-slate-500 focus:bg-white focus:ring-2 focus:ring-slate-200"><button type="button" @click="showCurrent = !showCurrent" :aria-label="showCurrent ? 'Hide current password' : 'Show current password'" class="absolute inset-y-0 right-0 w-11 text-xs font-bold text-slate-500 hover:text-slate-900" x-text="showCurrent ? 'Hide' : 'Show'"></button></div>
                @error('current_password')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-600">New password</label>
                <div class="relative"><input :type="showNew ? 'text' : 'password'" id="password" wire:model="password" autocomplete="new-password" class="min-h-[46px] w-full rounded-xl border border-slate-200 bg-slate-50 px-3 pr-11 text-sm outline-none focus:border-slate-500 focus:bg-white focus:ring-2 focus:ring-slate-200"><button type="button" @click="showNew = !showNew" :aria-label="showNew ? 'Hide new password' : 'Show new password'" class="absolute inset-y-0 right-0 w-11 text-xs font-bold text-slate-500 hover:text-slate-900" x-text="showNew ? 'Hide' : 'Show'"></button></div>
                @error('password')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-600">Confirm new password</label>
                <div class="relative"><input :type="showConfirmation ? 'text' : 'password'" id="password_confirmation" wire:model="password_confirmation" autocomplete="new-password" class="min-h-[46px] w-full rounded-xl border border-slate-200 bg-slate-50 px-3 pr-11 text-sm outline-none focus:border-slate-500 focus:bg-white focus:ring-2 focus:ring-slate-200"><button type="button" @click="showConfirmation = !showConfirmation" :aria-label="showConfirmation ? 'Hide password confirmation' : 'Show password confirmation'" class="absolute inset-y-0 right-0 w-11 text-xs font-bold text-slate-500 hover:text-slate-900" x-text="showConfirmation ? 'Hide' : 'Show'"></button></div>
            </div>
            <button type="submit" wire:loading.attr="disabled" class="min-h-[46px] w-full rounded-xl bg-slate-900 px-4 text-sm font-bold text-white hover:bg-slate-700 disabled:cursor-wait disabled:opacity-60">Update password</button>
        </form>
    </section>
</div>
