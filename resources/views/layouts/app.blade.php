<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'ALAS OS — Operational Suite' }}</title>
    <meta name="description" content="ALAS OS — Inventory, Orders & Finance Control">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    @livewireStyles
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        ::-webkit-scrollbar { width: 3px; height: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 4px; }

        /* Safe area for notch phones */
        .pb-safe { padding-bottom: env(safe-area-inset-bottom, 0px); }

        /* Smooth transitions */
        * { -webkit-tap-highlight-color: transparent; }

        /* Active card press effect */
        .card-press:active { transform: scale(0.98); }

        /* Progress bar animation */
        @keyframes grow { from { width: 0 } }
        .progress-bar { animation: grow 0.8s ease-out; }
    </style>
</head>
<body class="h-full bg-[#1A1A1E] text-white overflow-x-hidden" x-data="{ drawerOpen: false }">

{{-- ═══ DRAWER OVERLAY ═══ --}}
<div x-show="drawerOpen"
     @click="drawerOpen=false"
     class="fixed inset-0 z-40 bg-black/70 backdrop-blur-[2px]"
     x-transition:enter="transition-opacity duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display:none">
</div>

{{-- ═══ LEFT DRAWER ═══ --}}
<aside :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed left-0 top-0 bottom-0 z-50 w-[300px] bg-[#111111] border-r border-white/[0.08] transform transition-transform duration-250 ease-out overflow-y-auto flex flex-col">

    {{-- Drawer Header --}}
    <div class="flex items-center justify-between px-5 pt-12 pb-5 border-b border-white/[0.08]">
        <div>
            <div class="text-[15px] font-black text-white tracking-tight leading-none">ALAS OS</div>
            <div class="text-[10px] text-white/30 uppercase tracking-[0.15em] font-semibold mt-1">Operational Suite</div>
        </div>
        <button @click="drawerOpen=false"
                class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Drawer Nav --}}
    <nav class="flex-1 px-3 py-4 space-y-0.5">

        <a href="{{ route('dashboard') }}" @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all duration-150
                  {{ request()->routeIs('dashboard') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
            </svg>
            Dashboard
        </a>

        <a href="{{ route('products.index') }}" @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all duration-150
                  {{ request()->routeIs('products.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            Products
        </a>

        <a href="{{ route('inventory.index') }}" @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all duration-150
                  {{ request()->routeIs('inventory.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Inventory
            @php $_lowStockCount = \App\Models\Inventory::whereColumn('current_stock','<=','min_stock_threshold')->count(); @endphp
            @if($_lowStockCount > 0)
                <span class="ml-auto text-[10px] font-black bg-red-500 text-white px-2 py-0.5 rounded-full">{{ $_lowStockCount }}</span>
            @endif
        </a>

        <a href="{{ route('orders.index') }}" @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all duration-150
                  {{ request()->routeIs('orders.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            Orders
            @php $_pendingOrders = \App\Models\Order::where('order_status','pending')->count(); @endphp
            @if($_pendingOrders > 0)
                <span class="ml-auto text-[10px] font-black bg-white text-[#111111] px-2 py-0.5 rounded-full">{{ $_pendingOrders }}</span>
            @endif
        </a>

        @if(auth()->check() && auth()->user()->canAccessFinance())
        <a href="{{ route('finance.index') }}" @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all duration-150
                  {{ request()->routeIs('finance.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Finance
        </a>

        <a href="{{ route('reports.index') }}" @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all duration-150
                  {{ request()->routeIs('reports.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Analytics
        </a>
        @endif

        <div class="!my-4 border-t border-white/[0.08]"></div>

        <a href="{{ route('activity-logs.index') }}" @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all duration-150
                  {{ request()->routeIs('activity-logs.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Activity Logs
        </a>

        @if(auth()->check() && auth()->user()->isOwner())
        <a href="{{ route('users.index') }}" @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all duration-150
                  {{ request()->routeIs('users.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
            </svg>
            Users
        </a>
        @endif

        <a href="{{ route('settings.index') }}" @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all duration-150
                  {{ request()->routeIs('settings.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
            Settings
        </a>
    </nav>

    {{-- Drawer Footer: User + Sign Out --}}
    <div class="border-t border-white/[0.08] px-5 py-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center flex-shrink-0">
                <span class="text-[13px] font-black text-[#111111]">
                    {{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 1)) : 'U' }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[13px] font-bold text-white truncate">{{ auth()->check() ? auth()->user()->name : 'Guest' }}</div>
                <div class="text-[11px] text-white/30 capitalize">{{ auth()->check() ? auth()->user()->role : '' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Sign out"
                        class="w-8 h-8 flex items-center justify-center rounded-full bg-white/10 text-white/40 hover:bg-red-500/20 hover:text-red-400 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- ═══ FIXED TOP BAR ═══ --}}
<header class="fixed top-0 left-0 right-0 z-30 h-14 bg-[#1A1A1E]/95 backdrop-blur-md border-b border-white/[0.06] flex items-center px-4 gap-3">

    {{-- Hamburger --}}
    <button @click="drawerOpen=true"
            class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-white/10 transition-colors flex-shrink-0">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    {{-- Brand --}}
    <div class="flex-1 text-center">
        <div class="text-[13px] font-black text-white tracking-[0.02em] leading-none">ALAS OPERATING SYSTEM</div>
    </div>

    {{-- Bell --}}
    <button class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-white/10 transition-colors relative flex-shrink-0">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @php $_bell_low = \App\Models\Inventory::whereColumn('current_stock','<=','min_stock_threshold')->count(); @endphp
        @if($_bell_low > 0)
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-[1.5px] border-[#1A1A1E]"></span>
        @endif
    </button>
</header>

{{-- ═══ MAIN CONTENT ═══ --}}
<main class="pt-14 pb-20 min-h-screen">

    {{-- Flash: Success --}}
    @if(session()->has('success'))
        <div class="mx-4 mt-4 flex items-center gap-3 px-4 py-3 bg-emerald-900/50 border border-emerald-600/40 rounded-2xl text-emerald-300"
             x-data="{ show: true }" x-show="show" x-transition>
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            <span class="flex-1 font-semibold text-[13px]">{{ session('success') }}</span>
            <button @click="show=false">
                <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Flash: Error --}}
    @if(session()->has('error'))
        <div class="mx-4 mt-4 flex items-center gap-3 px-4 py-3 bg-red-900/50 border border-red-600/40 rounded-2xl text-red-300"
             x-data="{ show: true }" x-show="show" x-transition>
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
            <span class="flex-1 font-semibold text-[13px]">{{ session('error') }}</span>
            <button @click="show=false">
                <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Page Content --}}
    <div class="px-4 py-4">
        {{ $slot }}
    </div>
</main>

{{-- ═══ FIXED BOTTOM NAVIGATION ═══ --}}
<nav class="fixed bottom-0 left-0 right-0 z-30 bg-[#111111]/98 backdrop-blur-md border-t border-white/[0.08] pb-safe">
    <div class="flex items-center h-16 px-2">

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl transition-all duration-200 card-press
                  {{ request()->routeIs('dashboard') ? '' : '' }}">
            <div class="transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-white rounded-[10px] p-2' : 'p-2' }}">
                <svg class="w-5 h-5 transition-colors {{ request()->routeIs('dashboard') ? 'text-[#111111]' : 'text-white/40' }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                </svg>
            </div>
            <span class="text-[10px] font-bold transition-colors {{ request()->routeIs('dashboard') ? 'text-white' : 'text-white/30' }}">Dashboard</span>
        </a>

        {{-- Orders --}}
        <a href="{{ route('orders.index') }}"
           class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl transition-all duration-200 card-press">
            <div class="relative transition-all duration-200 {{ request()->routeIs('orders.*') ? 'bg-white rounded-[10px] p-2' : 'p-2' }}">
                <svg class="w-5 h-5 transition-colors {{ request()->routeIs('orders.*') ? 'text-[#111111]' : 'text-white/40' }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                @if(isset($_pendingOrders) && $_pendingOrders > 0 && !request()->routeIs('orders.*'))
                    <span class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-red-500 rounded-full border border-[#111111]"></span>
                @endif
            </div>
            <span class="text-[10px] font-bold transition-colors {{ request()->routeIs('orders.*') ? 'text-white' : 'text-white/30' }}">Orders</span>
        </a>

        {{-- Inventory --}}
        <a href="{{ route('inventory.index') }}"
           class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl transition-all duration-200 card-press">
            <div class="transition-all duration-200 {{ request()->routeIs('inventory.*') ? 'bg-white rounded-[10px] p-2' : 'p-2' }}">
                <svg class="w-5 h-5 transition-colors {{ request()->routeIs('inventory.*') ? 'text-[#111111]' : 'text-white/40' }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <span class="text-[10px] font-bold transition-colors {{ request()->routeIs('inventory.*') ? 'text-white' : 'text-white/30' }}">Inventory</span>
        </a>

        {{-- Finance (owners/managers) or Reports (staff) --}}
        @if(auth()->check() && auth()->user()->canAccessFinance())
        <a href="{{ route('finance.index') }}"
           class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl transition-all duration-200 card-press">
            <div class="transition-all duration-200 {{ request()->routeIs('finance.*') ? 'bg-white rounded-[10px] p-2' : 'p-2' }}">
                <svg class="w-5 h-5 transition-colors {{ request()->routeIs('finance.*') ? 'text-[#111111]' : 'text-white/40' }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-[10px] font-bold transition-colors {{ request()->routeIs('finance.*') ? 'text-white' : 'text-white/30' }}">Finance</span>
        </a>
        @else
        <a href="{{ route('reports.index') }}"
           class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl transition-all duration-200 card-press">
            <div class="transition-all duration-200 {{ request()->routeIs('reports.*') ? 'bg-white rounded-[10px] p-2' : 'p-2' }}">
                <svg class="w-5 h-5 transition-colors {{ request()->routeIs('reports.*') ? 'text-[#111111]' : 'text-white/40' }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <span class="text-[10px] font-bold transition-colors {{ request()->routeIs('reports.*') ? 'text-white' : 'text-white/30' }}">Analytics</span>
        </a>
        @endif

    </div>
</nav>

@livewireScripts
</body>
</html>
