<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Business Manager</title>
    <meta name="description" content="Secure sign in for Business Manager.">
    <meta name="theme-color" content="#000000">
    <link rel="icon" type="image/png" href="{{ asset('images/alas-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/alas-logo.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Business Manager">
    <meta property="og:image" content="{{ url('/images/alas-logo-master.png') }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:image" content="{{ url('/images/alas-logo-master.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        body { font-family: 'Inter', sans-serif; }
        @keyframes login-rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
        .login-auth-card { animation: login-rise 420ms cubic-bezier(.22, .8, .25, 1) both; }
        @media (prefers-reduced-motion: reduce) { .login-auth-card { animation: none; } }
    </style>
</head>
<body class="min-h-[100dvh] bg-white text-[#111111]">
    <div class="grid min-h-[100dvh] lg:grid-cols-[minmax(0,1.05fr)_minmax(420px,0.95fr)]">
        <section class="relative flex min-h-[100dvh] flex-col bg-white px-5 pt-6 pb-[calc(1.5rem+env(safe-area-inset-bottom))] sm:px-8 sm:pt-8 lg:px-12 lg:py-9 xl:px-16">
            <header class="flex items-center justify-center lg:justify-start">
                <img src="{{ asset('images/alas-logo.png') }}" alt="Business Manager" class="h-12 w-12 rounded-2xl object-cover shadow-sm">
            </header>

            <main class="flex flex-1 items-center justify-center py-8 sm:py-10 lg:py-14">
                {{ $slot }}
            </main>

            <footer class="flex flex-col items-center gap-2 text-[10px] font-medium text-[#A3A3A3] sm:flex-row sm:justify-between lg:items-end">
                <span>Authorized access only</span>
                <span>Secure business workspace</span>
            </footer>
        </section>

        <aside class="relative hidden min-h-screen overflow-hidden bg-[#111111] lg:block" aria-label="Business Manager editorial background">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('images/login-editorial.png') }}')"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/35 to-black/10"></div>
            <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.055)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.055)_1px,transparent_1px)] bg-[size:42px_42px] opacity-35"></div>

            <div class="relative flex h-full flex-col justify-end p-10 xl:p-14">
                <div class="max-w-md border-l border-white/50 pl-5">
                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-white/60">Business Manager</p>
                    <h2 class="mt-3 text-4xl font-black leading-[1.05] tracking-tight text-white xl:text-5xl">Operations made clear.</h2>
                    <p class="mt-5 max-w-sm text-[14px] leading-relaxed text-white/70">Manage orders, inventory, and day-to-day work from one focused workspace.</p>
                </div>
            </div>
        </aside>
    </div>
    @livewireScripts
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js?v=5'));
        }
    </script>
</body>
</html>
