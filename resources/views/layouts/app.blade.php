<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Business Manager' }}</title>
    <meta name="description" content="Inventory, orders, and finance management in one workspace.">
    <meta name="application-name" content="Business Manager">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-title" content="Business Manager">
    <link rel="icon" type="image/png" href="{{ asset('images/alas-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/alas-logo.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Business Manager">
    <meta property="og:description" content="Inventory, orders, and finance management in one workspace.">
    <meta property="og:image" content="{{ url('/images/alas-logo-master.png') }}">
    <meta property="og:image:width" content="800">
    <meta property="og:image:height" content="800">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Business Manager">
    <meta name="twitter:description" content="Inventory, orders, and finance management in one workspace.">
    <meta name="twitter:image" content="{{ url('/images/alas-logo-master.png') }}">

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
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.12); border-radius: 4px; }
        * { -webkit-tap-highlight-color: transparent; }
        [x-cloak] { display: none !important; }
        .card-press:active { transform: scale(0.98); }
        @keyframes grow { from { width: 0 } }
        .progress-bar { animation: grow 0.8s ease-out; }
        @keyframes skeleton-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .animate-skeleton { background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%); background-size: 200% 100%; animation: skeleton-shimmer 1.4s ease-in-out infinite; }
        .navigation-skeleton { opacity: 0; pointer-events: none; transition: opacity 120ms ease; }
        .page-content { transition: opacity 140ms ease, transform 140ms ease; }
        .is-navigating .navigation-skeleton { opacity: 1; pointer-events: auto; }
        .is-navigating .page-content { opacity: 0; transform: translateY(4px); pointer-events: none; }
        @keyframes app-modal-sheet-in { from { opacity: 0; transform: translateY(100%); } to { opacity: 1; transform: translateY(0); } }
        @keyframes app-modal-dialog-in { from { opacity: 0; transform: translateY(8px) scale(.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .app-modal-sheet { animation: app-modal-sheet-in 260ms cubic-bezier(.22, .8, .25, 1) both; }
        @media (min-width: 640px) { .app-modal-sheet { animation-name: app-modal-dialog-in; } }
        @media (prefers-reduced-motion: reduce) { .animate-skeleton, .page-content, .navigation-skeleton, .app-modal-sheet { animation: none; transition: none; } }

        /* Mobile bottom nav safe area */
        .pb-safe { padding-bottom: env(safe-area-inset-bottom, 0px); }

        /* Mobile navigation stays in the same light visual system as the desktop app. */
        @media (max-width: 1023px) {
            ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.12); }
            aside.lg\\:hidden, header.lg\\:hidden, nav.lg\\:hidden { background: #ffffff !important; border-color: #e8e8e8 !important; }
            aside.lg\\:hidden .text-white, header.lg\\:hidden .text-white { color: #111111 !important; }
            aside.lg\\:hidden .text-white\\/60, aside.lg\\:hidden .text-white\\/40, aside.lg\\:hidden .text-white\\/30,
            nav.lg\\:hidden .text-white\\/40, nav.lg\\:hidden .text-white\\/30 { color: #666666 !important; }
            aside.lg\\:hidden .border-white\\/\\[0\\.08\\], header.lg\\:hidden .border-white\\/\\[0\\.06\\], nav.lg\\:hidden .border-white\\/\\[0\\.08\\] { border-color: #e8e8e8 !important; }
            aside.lg\\:hidden .bg-white\\/10 { background: #f5f5f5 !important; }
            nav.lg\\:hidden .bg-white { background: #111111 !important; }
            nav.lg\\:hidden .bg-white + svg, nav.lg\\:hidden .bg-white svg { color: #ffffff !important; }
        }
    </style>
</head>

{{-- ═══════════════════════════════════════════════════════════
     MOBILE: Dark bg + drawer + bottom nav  (< lg)
     DESKTOP: Light bg + fixed sidebar      (>= lg)
     ═══════════════════════════════════════════════════════════ --}}
<body class="h-full overflow-x-hidden bg-[#F2F2F2] lg:bg-[#F2F2F2]"
      x-data="{ drawerOpen: false, notificationsOpen: false }"
      @keydown.escape.window="notificationsOpen = false">

@php
    $_notifications = auth()->check()
        ? \App\Models\Notification::where('user_id', auth()->id())->latest()->take(10)->get()
        : collect();
    $_unreadNotificationCount = $_notifications->where('is_read', false)->count();
@endphp
<div x-data="{ online: navigator.onLine, pending: 0 }" @alas-sync-status.window="online = $event.detail.online; pending = $event.detail.pending" class="fixed inset-x-0 top-14 z-40 lg:top-0">
    <div x-show="!online || pending" x-cloak class="mx-auto max-w-3xl rounded-b-2xl border border-amber-200 bg-amber-50 px-4 py-2 text-center text-[12px] font-semibold text-amber-900">
        <span x-text="!online ? 'You are offline. New sales and permitted cash transactions will be saved on this device.' : `${pending} transaction${pending === 1 ? '' : 's'} waiting to synchronize.`"></span>
        <button type="button" x-show="online && pending" @click="window.AlasOffline.sync()" class="ml-2 underline">Sync now</button>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MOBILE-ONLY: Drawer Overlay + Left Drawer + Top Bar + Bottom Nav
     ══════════════════════════════════════════════════════ --}}

{{-- Drawer Overlay --}}
<div x-show="drawerOpen"
     @click="drawerOpen=false"
     class="lg:hidden fixed inset-0 z-40 bg-black/70 backdrop-blur-[2px]"
     x-transition:enter="transition-opacity duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display:none">
</div>

{{-- Mobile Left Drawer --}}
<aside :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'"
       class="lg:hidden fixed left-0 top-0 bottom-0 z-50 w-[280px] bg-[#111111] border-r border-white/[0.08]
              transform transition-transform duration-250 ease-out overflow-y-auto flex flex-col">

    <div class="flex items-center justify-between px-5 pt-12 pb-5 border-b border-white/[0.08]">
        <img src="{{ asset('images/alas-logo.png') }}" alt="Business Manager" class="h-11 w-11 rounded-xl object-cover">
        <button @click="drawerOpen=false"
                class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-0.5">
        @php $_lowStockCount = \App\Models\Inventory::whereColumn('current_stock','<=','min_stock_threshold')->count();
               $_pendingOrders = \App\Models\Order::where('order_status','pending')->count(); @endphp

        <a href="{{ route('dashboard') }}" wire:navigate @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all
                  {{ request()->routeIs('dashboard') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
            </svg>
            Dashboard
        </a>

        <a href="{{ route('products.index') }}" wire:navigate @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all
                  {{ request()->routeIs('products.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            Products
        </a>

        <a href="{{ route('inventory.index') }}" wire:navigate @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all
                  {{ request()->routeIs('inventory.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Inventory
            @if($_lowStockCount > 0)
                <span class="ml-auto text-[10px] font-black bg-red-500 text-white px-2 py-0.5 rounded-full">{{ $_lowStockCount }}</span>
            @endif
        </a>

        <a href="{{ route('orders.index') }}" wire:navigate @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all
                  {{ request()->routeIs('orders.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            Orders
            @if($_pendingOrders > 0)
                <span class="ml-auto text-[10px] font-black bg-white text-[#111111] px-2 py-0.5 rounded-full">{{ $_pendingOrders }}</span>
            @endif
        </a>

        @if(auth()->check() && auth()->user()->canViewFinance())
        <a href="{{ route('finance.index') }}" wire:navigate @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all
                  {{ request()->routeIs('finance.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Finance
        </a>

        <a href="{{ route('reports.index') }}" wire:navigate @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all
                  {{ request()->routeIs('reports.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Analytics
        </a>
        @endif

        <div class="!my-4 border-t border-white/[0.08]"></div>

        <a href="{{ route('activity-logs.index') }}" wire:navigate @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all
                  {{ request()->routeIs('activity-logs.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Activity Logs
        </a>

        @if(auth()->check() && auth()->user()->isOwner())
        <a href="{{ route('users.index') }}" wire:navigate @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all
                  {{ request()->routeIs('users.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
            </svg>
            Users
        </a>
        @endif

        @if(auth()->check() && auth()->user()->canManageSettings())
        <a href="{{ route('settings.index') }}" wire:navigate @click="drawerOpen=false"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-[13px] font-semibold transition-all
                  {{ request()->routeIs('settings.*') ? 'bg-white text-[#111111]' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
            Settings
        </a>
        @endif
    </nav>

    <div class="border-t border-white/[0.08] px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-white flex items-center justify-center flex-shrink-0">
                <span class="text-[12px] font-black text-[#111111]">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[13px] font-bold text-white truncate">{{ auth()->user()->name ?? 'Guest' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-full bg-white/10 text-white/40 hover:bg-red-500/20 hover:text-red-400 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- Mobile Top Bar --}}
<header class="lg:hidden fixed top-0 left-0 right-0 z-30 h-14 bg-[#1A1A1E]/95 backdrop-blur-md border-b border-white/[0.06] flex items-center px-4 gap-3">
    <button @click="drawerOpen=true"
            class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-white/10 transition-colors flex-shrink-0">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>
    <div class="flex-1 flex justify-center">
        <img src="{{ asset('images/alas-logo.png') }}" alt="Business Manager" class="h-11 w-11 rounded-xl object-cover">
    </div>
    <button type="button" @click="notificationsOpen = true" :aria-expanded="notificationsOpen.toString()" aria-controls="mobile-notifications"
            class="w-11 h-11 flex items-center justify-center rounded-xl hover:bg-white/10 transition-colors relative flex-shrink-0">
        <span class="sr-only">Open notifications</span>
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if($_unreadNotificationCount > 0)
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-[1.5px] border-[#1A1A1E]"></span>
        @endif
    </button>
</header>

{{-- Mobile notification bottom sheet --}}
<div id="mobile-notifications" x-show="notificationsOpen" x-cloak class="lg:hidden fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-label="Notifications">
    <div class="absolute inset-0 bg-black/30" @click="notificationsOpen = false" x-transition.opacity></div>
    <section class="absolute inset-x-0 bottom-0 max-h-[78vh] rounded-t-3xl bg-white shadow-2xl" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
        <div class="flex items-center justify-between border-b border-[#E8E8E8] px-5 py-4">
            <div><h2 class="text-[16px] font-bold text-[#111111]">Notifications</h2><p class="text-[12px] text-[#777777]">{{ $_unreadNotificationCount ? $_unreadNotificationCount . ' unread' : 'You’re all caught up' }}</p></div>
            <button type="button" @click="notificationsOpen = false" class="min-h-[44px] min-w-[44px] rounded-xl text-[#555555]" aria-label="Close notifications">✕</button>
        </div>
        <div class="max-h-[60vh] overflow-y-auto p-3">
            @forelse($_notifications as $notification)
                @if($notification->link)
                    <a href="{{ $notification->link }}" wire:navigate @click="notificationsOpen = false" class="mb-2 block rounded-2xl p-4 transition-colors {{ $notification->is_read ? 'bg-white' : 'bg-[#F5F7FA]' }}">
                        <div class="flex gap-3"><span class="mt-1 h-2 w-2 flex-shrink-0 rounded-full {{ $notification->is_read ? 'bg-[#D0D0D0]' : 'bg-blue-500' }}"></span><div><p class="text-[13px] font-bold text-[#222222]">{{ $notification->title }}</p><p class="mt-1 text-[12px] leading-relaxed text-[#666666]">{{ $notification->message }}</p><p class="mt-2 text-[11px] text-[#999999]">{{ $notification->created_at->diffForHumans() }}</p></div></div>
                    </a>
                @else
                    <div class="mb-2 rounded-2xl p-4 {{ $notification->is_read ? 'bg-white' : 'bg-[#F5F7FA]' }}"><p class="text-[13px] font-bold text-[#222222]">{{ $notification->title }}</p><p class="mt-1 text-[12px] leading-relaxed text-[#666666]">{{ $notification->message }}</p><p class="mt-2 text-[11px] text-[#999999]">{{ $notification->created_at->diffForHumans() }}</p></div>
                @endif
            @empty
                <div class="px-5 py-12 text-center"><div class="text-[14px] font-semibold text-[#555555]">No notifications yet</div><p class="mt-1 text-[12px] text-[#888888]">Updates assigned to you will appear here.</p></div>
            @endforelse
        </div>
    </section>
</div>

{{-- Mobile Bottom Navigation --}}
<nav class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-[#111111] border-t border-white/[0.08] pb-safe">
    <div class="flex items-center h-16 px-1">

        <a href="{{ route('dashboard') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl">
            <div class="transition-all {{ request()->routeIs('dashboard') ? 'bg-white rounded-[10px] p-1.5' : 'p-1.5' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-[#111111]' : 'text-white/40' }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                </svg>
            </div>
            <span class="text-[10px] font-bold {{ request()->routeIs('dashboard') ? 'text-white' : 'text-white/30' }}">Dashboard</span>
        </a>

        <a href="{{ route('orders.index') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl">
            <div class="relative transition-all {{ request()->routeIs('orders.*') ? 'bg-white rounded-[10px] p-1.5' : 'p-1.5' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('orders.*') ? 'text-[#111111]' : 'text-white/40' }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                @if(isset($_pendingOrders) && $_pendingOrders > 0 && !request()->routeIs('orders.*'))
                    <span class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-red-500 rounded-full border border-[#111111]"></span>
                @endif
            </div>
            <span class="text-[10px] font-bold {{ request()->routeIs('orders.*') ? 'text-white' : 'text-white/30' }}">Orders</span>
        </a>

        <a href="{{ route('inventory.index') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl">
            <div class="transition-all {{ request()->routeIs('inventory.*') ? 'bg-white rounded-[10px] p-1.5' : 'p-1.5' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('inventory.*') ? 'text-[#111111]' : 'text-white/40' }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <span class="text-[10px] font-bold {{ request()->routeIs('inventory.*') ? 'text-white' : 'text-white/30' }}">Inventory</span>
        </a>

        @if(auth()->check() && auth()->user()->canViewFinance())
        <a href="{{ route('finance.index') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl">
            <div class="transition-all {{ request()->routeIs('finance.*') ? 'bg-white rounded-[10px] p-1.5' : 'p-1.5' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('finance.*') ? 'text-[#111111]' : 'text-white/40' }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-[10px] font-bold {{ request()->routeIs('finance.*') ? 'text-white' : 'text-white/30' }}">Finance</span>
        </a>
        @else
        <a href="{{ route('reports.index') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center gap-1 py-2 rounded-xl">
            <div class="transition-all {{ request()->routeIs('reports.*') ? 'bg-white rounded-[10px] p-1.5' : 'p-1.5' }}">
                <svg class="w-5 h-5 {{ request()->routeIs('reports.*') ? 'text-[#111111]' : 'text-white/40' }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <span class="text-[10px] font-bold {{ request()->routeIs('reports.*') ? 'text-white' : 'text-white/30' }}">Analytics</span>
        </a>
        @endif

    </div>
</nav>


{{-- ══════════════════════════════════════════════════════
     DESKTOP LAYOUT: Fixed sidebar + top header + main content
     ══════════════════════════════════════════════════════ --}}
<div class="flex min-h-screen">

    {{-- ─── Fixed Sidebar ─── --}}
    <aside class="fixed left-0 top-0 bottom-0 hidden w-[220px] xl:w-[240px] bg-white border-r border-[#E8E8E8] lg:flex flex-col z-20 overflow-y-auto">

        {{-- Brand mark --}}
        <div class="px-5 py-4 border-b border-[#F0F0F0]">
            <img src="{{ asset('images/alas-logo.png') }}" alt="Business Manager" class="h-12 w-12 rounded-xl object-cover">
        </div>

        {{-- Nav Links --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5">
            @php $_lowStockCount = \App\Models\Inventory::whereColumn('current_stock','<=','min_stock_threshold')->count();
                   $_pendingOrders = \App\Models\Order::where('order_status','pending')->count(); @endphp

            <a href="{{ route('dashboard') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all
                      {{ request()->routeIs('dashboard') ? 'bg-[#111111] text-white' : 'text-[#555555] hover:bg-[#F5F5F5] hover:text-[#111111]' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('products.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all
                      {{ request()->routeIs('products.*') ? 'bg-[#111111] text-white' : 'text-[#555555] hover:bg-[#F5F5F5] hover:text-[#111111]' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Products
            </a>

            <a href="{{ route('inventory.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all
                      {{ request()->routeIs('inventory.*') ? 'bg-[#111111] text-white' : 'text-[#555555] hover:bg-[#F5F5F5] hover:text-[#111111]' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Inventory
                @if($_lowStockCount > 0)
                    <span class="ml-auto text-[10px] font-black bg-red-500 text-white px-1.5 py-0.5 rounded-full">{{ $_lowStockCount }}</span>
                @endif
            </a>

            <a href="{{ route('orders.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all
                      {{ request()->routeIs('orders.*') ? 'bg-[#111111] text-white' : 'text-[#555555] hover:bg-[#F5F5F5] hover:text-[#111111]' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                Orders
                @if($_pendingOrders > 0)
                    <span class="ml-auto text-[10px] font-black bg-[#111111] {{ request()->routeIs('orders.*') ? 'bg-white text-[#111111]' : 'bg-[#F0F0F0] text-[#555555]' }} px-1.5 py-0.5 rounded-full">{{ $_pendingOrders }}</span>
                @endif
            </a>

            @if(auth()->check() && auth()->user()->canViewFinance())
            <a href="{{ route('finance.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all
                      {{ request()->routeIs('finance.*') ? 'bg-[#111111] text-white' : 'text-[#555555] hover:bg-[#F5F5F5] hover:text-[#111111]' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Finance
            </a>

            <a href="{{ route('reports.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all
                      {{ request()->routeIs('reports.*') ? 'bg-[#111111] text-white' : 'text-[#555555] hover:bg-[#F5F5F5] hover:text-[#111111]' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Analytics
            </a>
            @endif

            <div class="!my-4 border-t border-[#F0F0F0]"></div>

            <a href="{{ route('activity-logs.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all
                      {{ request()->routeIs('activity-logs.*') ? 'bg-[#111111] text-white' : 'text-[#555555] hover:bg-[#F5F5F5] hover:text-[#111111]' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Activity Logs
            </a>

            @if(auth()->check() && auth()->user()->isOwner())
            <a href="{{ route('users.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all
                      {{ request()->routeIs('users.*') ? 'bg-[#111111] text-white' : 'text-[#555555] hover:bg-[#F5F5F5] hover:text-[#111111]' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                </svg>
                Users
            </a>
            @endif

            @if(auth()->check() && auth()->user()->canManageSettings())
            <a href="{{ route('settings.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-semibold transition-all
                      {{ request()->routeIs('settings.*') ? 'bg-[#111111] text-white' : 'text-[#555555] hover:bg-[#F5F5F5] hover:text-[#111111]' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                </svg>
                Settings
            </a>
            @endif
        </nav>

        {{-- User + Logout --}}
        <div class="border-t border-[#F0F0F0] px-4 py-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-[#111111] flex items-center justify-center flex-shrink-0">
                    <span class="text-[11px] font-black text-white">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-[12px] font-bold text-[#111111] truncate">{{ auth()->user()->name ?? 'Guest' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Sign out"
                            class="w-7 h-7 flex items-center justify-center rounded-lg text-[#AAAAAA] hover:bg-red-50 hover:text-red-500 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ─── Desktop Right Column (top bar + content) ─── --}}
    <div class="flex-1 flex flex-col ml-0 lg:ml-[220px] xl:ml-[240px] min-h-screen">

        {{-- Desktop Top Header --}}
        <header class="sticky top-0 z-10 hidden bg-white border-b border-[#E8E8E8] lg:flex items-center h-14 px-6 gap-4">
            <div class="flex-1">
                <h1 class="text-[15px] font-bold text-[#111111] leading-none">
                    @if(request()->routeIs('dashboard'))         Business Command Center
                    @elseif(request()->routeIs('orders.create')) Create New Order
                    @elseif(request()->routeIs('orders.*'))      Orders
                    @elseif(request()->routeIs('products.create')) Add Product
                    @elseif(request()->routeIs('products.*'))    Products
                    @elseif(request()->routeIs('inventory.*'))   Inventory
                    @elseif(request()->routeIs('finance.*'))     Finance
                    @elseif(request()->routeIs('reports.*'))     Analytics
                    @elseif(request()->routeIs('activity-logs.*')) Activity Logs
                    @elseif(request()->routeIs('users.*'))       Users
                    @elseif(request()->routeIs('settings.*'))    Settings
                    @else Business Manager
                    @endif
                </h1>
            </div>

            {{-- Alerts badge --}}
            @if(isset($_lowStockCount) && $_lowStockCount > 0)
            <a href="{{ route('inventory.index') }}" wire:navigate
               class="flex items-center gap-2 px-3 py-1.5 bg-red-50 border border-red-100 rounded-lg text-[12px] font-semibold text-red-600 hover:bg-red-100 transition-colors">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                {{ $_lowStockCount }} Low Stock
            </a>
            @endif

            {{-- User pill --}}
            <div class="flex items-center gap-2 pl-3 border-l border-[#F0F0F0]">
                <div class="w-7 h-7 rounded-full bg-[#111111] flex items-center justify-center">
                    <span class="text-[10px] font-black text-white">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                </div>
                <span class="text-[12px] font-semibold text-[#444444]">{{ auth()->user()->name ?? 'Guest' }}</span>
            </div>
        </header>

        {{-- Desktop Content --}}
        <main class="relative flex-1 p-4 pt-[72px] pb-20 lg:p-6" aria-live="polite" aria-busy="false" data-page-content>
            <div class="navigation-skeleton absolute inset-0 z-10 bg-[#F2F2F2]/95 p-4 lg:p-6" data-navigation-skeleton>
                <span class="sr-only">Loading page content</span>
                <x-skeleton.page />
            </div>
            <div class="page-content" data-page-content-body>

            @if(session()->has('success'))
                <div class="mb-5 flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700"
                     x-data="{ show: true }" x-show="show" x-transition>
                    <svg class="w-4 h-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                    <span class="flex-1 text-[13px] font-semibold">{{ session('success') }}</span>
                    <button @click="show=false"><svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
            @endif

            @if(session()->has('error'))
                <div class="mb-5 flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700"
                     x-data="{ show: true }" x-show="show" x-transition>
                    <svg class="w-4 h-4 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                    <span class="flex-1 text-[13px] font-semibold">{{ session('error') }}</span>
                    <button @click="show=false"><svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
            @endif

            {{ $slot }}
            </div>

        </main>
    </div>
</div>{{-- end responsive app shell --}}

@livewireScripts
<script src="/offline-sync.js"></script>
<script>
    window.AlasOffline?.setUser({{ auth()->id() ?? 'null' }});
    document.addEventListener('livewire:navigating', () => {
        document.documentElement.classList.add('is-navigating');
        document.querySelectorAll('[data-page-content]').forEach((content) => content.setAttribute('aria-busy', 'true'));
        window.scrollTo({ top: 0, behavior: 'auto' });
    });
    document.addEventListener('livewire:navigated', () => {
        document.documentElement.classList.remove('is-navigating');
        document.querySelectorAll('[data-page-content]').forEach((content) => content.setAttribute('aria-busy', 'false'));
    });
</script>
</body>
</html>
