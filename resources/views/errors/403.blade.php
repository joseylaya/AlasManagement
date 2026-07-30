<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied — Business Manager</title>
    <meta name="theme-color" content="#000000">
    <link rel="icon" type="image/png" href="{{ asset('images/alas-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/alas-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="h-full bg-[#F2F2F2] flex items-center justify-center p-6">
    <div class="text-center max-w-md">
        <div class="w-16 h-16 bg-red-50 border-2 border-red-100 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
        </div>
        <h1 class="text-[24px] font-black text-[#111111] mb-2">Access Denied</h1>
        <p class="text-[14px] text-[#888888] mb-2">
            You don't have permission to view this page.
        </p>
        <p class="text-[13px] text-[#AAAAAA] mb-8">
            @auth
                Logged in as <strong class="text-[#666666]">{{ auth()->user()->name }}</strong>.
                This section requires a higher access level.
            @else
                Please log in to continue.
            @endauth
        </p>
        <div class="flex gap-3 justify-center">
            @auth
            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-2 px-5 py-3 bg-[#111111] text-white text-[13px] font-semibold rounded-xl hover:bg-[#333333] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                Back to Dashboard
            </a>
            @else
            <a href="{{ route('login') }}" wire:navigate
               class="inline-flex items-center gap-2 px-5 py-3 bg-[#111111] text-white text-[13px] font-semibold rounded-xl hover:bg-[#333333] transition-colors">
                Go to Login
            </a>
            @endauth
        </div>
    </div>
</body>
</html>
