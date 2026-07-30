<div class="w-full max-w-[420px]">
    <div class="mb-7 text-center sm:mb-8 lg:text-left">
        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#888888]">Secure sign in</p>
        <h1 class="mt-2 text-[30px] font-black tracking-[-0.04em] text-[#111111] sm:text-[34px]">Welcome back</h1>
        <p class="mt-2 text-[13px] leading-relaxed text-[#777777]">Sign in to continue managing your business workspace.</p>
    </div>

    <div class="login-auth-card rounded-3xl border border-[#E5E5E5] bg-white p-5 shadow-[0_18px_45px_rgba(0,0,0,0.08)] sm:p-7">
        <form wire:submit.prevent="login" class="space-y-5">

            <div>
                <label for="email" class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-[#5F5F5F]">Work email</label>
                <input
                    type="email"
                    id="email"
                    wire:model="email"
                    autocomplete="email"
                    inputmode="email"
                    placeholder="name@company.com"
                    class="min-h-[50px] w-full rounded-2xl border border-[#DDDDDD] bg-[#FCFCFC] px-4 text-[14px] text-[#111111] outline-none transition placeholder:text-[#A5A5A5] focus:border-[#111111] focus:bg-white focus:ring-4 focus:ring-black/5"
                >
                @error('email') <p class="mt-1.5 text-[11px] font-medium text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-[#5F5F5F]">Password</label>
                <input
                    type="password"
                    id="password"
                    wire:model="password"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="min-h-[50px] w-full rounded-2xl border border-[#DDDDDD] bg-[#FCFCFC] px-4 text-[14px] text-[#111111] outline-none transition placeholder:text-[#A5A5A5] focus:border-[#111111] focus:bg-white focus:ring-4 focus:ring-black/5"
                >
                @error('password') <p class="mt-1.5 text-[11px] font-medium text-red-500">{{ $message }}</p> @enderror
            </div>

            <label class="flex min-h-[44px] cursor-pointer select-none items-center gap-2.5">
                <input type="checkbox" wire:model="remember" class="h-4 w-4 rounded border-[#CFCFCF] text-[#111111] focus:ring-[#111111] focus:ring-offset-0">
                <span class="text-[12px] font-medium text-[#666666]">Keep me signed in</span>
            </label>

            <button type="submit" wire:loading.attr="disabled" class="mt-1 flex min-h-[52px] w-full items-center justify-center gap-2 rounded-2xl bg-[#111111] px-5 text-[14px] font-bold text-white transition hover:bg-[#2C2C2C] disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove class="inline-flex items-center gap-2">Sign in <span aria-hidden="true" class="text-[19px] leading-none">→</span></span>
                <span wire:loading class="inline-flex items-center gap-2"><svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Signing in...</span>
            </button>
        </form>

        <div class="mt-6 flex items-center gap-3 text-[10px] font-semibold uppercase tracking-[0.14em] text-[#A3A3A3]"><span class="h-px flex-1 bg-[#ECECEC]"></span>Protected access<span class="h-px flex-1 bg-[#ECECEC]"></span></div>
    </div>
</div>
