<div class="w-full max-w-[400px]">

    {{-- Brand --}}
    <div class="mb-8">
        <div class="text-[28px] font-black text-[#111111] tracking-tight leading-none">ALAS OS</div>
        <div class="text-[11px] text-[#999999] uppercase tracking-widest font-semibold mt-1">Operational Suite</div>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl border border-[#E8E8E8] overflow-hidden shadow-sm">

        {{-- Card Header --}}
        <div class="px-7 pt-7 pb-5 border-b border-[#F0F0F0]">
            <h1 class="text-[18px] font-bold text-[#111111]">Sign in to your account</h1>
            <p class="text-[12px] text-[#888888] mt-1">Access the ALAS Business Manager.</p>
        </div>

        {{-- Form --}}
        <div class="px-7 py-6">
            <form wire:submit.prevent="login" class="space-y-4">

                <div>
                    <label for="email" class="block text-[11px] font-semibold text-[#555555] uppercase tracking-wider mb-1.5">
                        Email Address
                    </label>
                    <input
                        type="email"
                        id="email"
                        wire:model="email"
                        autocomplete="email"
                        placeholder="name@alasclothing.com"
                        class="w-full px-3.5 py-2.5 text-[13px] text-[#111111] bg-white border border-[#E0E0E0] rounded-lg focus:outline-none focus:border-[#111111] focus:ring-2 focus:ring-[#111111]/10 transition-all placeholder-[#BBBBBB]"
                    >
                    @error('email')
                        <p class="text-[11px] text-red-500 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-[11px] font-semibold text-[#555555] uppercase tracking-wider mb-1.5">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        wire:model="password"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full px-3.5 py-2.5 text-[13px] text-[#111111] bg-white border border-[#E0E0E0] rounded-lg focus:outline-none focus:border-[#111111] focus:ring-2 focus:ring-[#111111]/10 transition-all placeholder-[#BBBBBB]"
                    >
                    @error('password')
                        <p class="text-[11px] text-red-500 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" wire:model="remember"
                               class="w-3.5 h-3.5 rounded border-[#D0D0D0] text-[#111111] focus:ring-[#111111] focus:ring-offset-0">
                        <span class="text-[12px] text-[#666666] font-medium">Remember me</span>
                    </label>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full py-2.5 bg-[#111111] hover:bg-[#333333] disabled:opacity-50 text-white font-semibold text-[13px] rounded-lg transition-colors flex items-center justify-center gap-2 mt-2"
                >
                    <span wire:loading.remove>Sign In</span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Signing in...
                    </span>
                </button>
            </form>

            {{-- Demo Accounts --}}
            <div class="mt-6 pt-5 border-t border-[#F0F0F0]">
                <p class="text-[10px] font-semibold text-[#AAAAAA] uppercase tracking-widest text-center mb-3">Quick Demo Access</p>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        wire:click="demoLogin('owner')"
                        wire:loading.attr="disabled"
                        class="py-2 text-[11px] font-semibold text-[#555555] bg-white hover:bg-[#F5F5F5] border border-[#E0E0E0] rounded-lg transition-colors"
                    >
                        👑 Owner
                    </button>
                    <button
                        type="button"
                        wire:click="demoLogin('manager')"
                        wire:loading.attr="disabled"
                        class="py-2 text-[11px] font-semibold text-[#555555] bg-white hover:bg-[#F5F5F5] border border-[#E0E0E0] rounded-lg transition-colors"
                    >
                        👔 Manager
                    </button>
                    <button
                        type="button"
                        wire:click="demoLogin('staff')"
                        wire:loading.attr="disabled"
                        class="py-2 text-[11px] font-semibold text-[#555555] bg-white hover:bg-[#F5F5F5] border border-[#E0E0E0] rounded-lg transition-colors"
                    >
                        📦 Staff
                    </button>
                </div>
            </div>
        </div>
    </div>

    <p class="text-center text-[10px] text-[#BBBBBB] mt-6 font-medium">
        Authorized Personnel Only &middot; ALAS Clothing Internal System
    </p>
</div>
