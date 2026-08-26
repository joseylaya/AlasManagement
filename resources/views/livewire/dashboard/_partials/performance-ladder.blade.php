<section class="rounded-2xl border border-orange-200 bg-gradient-to-br from-[#FFF4E6] via-white to-[#FFE2B8] p-5 shadow-sm">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-[#F05A0A]">Performance Hall of Fame</p>
            <h3 class="mt-1 text-xl font-bold leading-tight tracking-tight text-slate-900">Climb with every valid activity and order</h3>
            <p class="mt-1.5 text-[13px] leading-5 text-slate-600">Points are recognition only—financial incentives stay separate.</p>
        </div>
        <div class="rounded-2xl bg-[#111111] px-5 py-3 text-white shadow-lg">
            <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-[#FFB11B]">Your standing</p>
            <p class="mt-1 flex items-baseline gap-2 text-2xl font-bold tracking-tight"><span>#{{ $myPerformanceRank ?? '—' }}</span><span class="text-sm font-medium tracking-normal text-[#FFB11B]">{{ $myPerformance['total'] }} pts</span></p>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-3 gap-2 text-center">
        <div class="rounded-xl border border-white bg-white/80 p-3"><p class="text-lg font-bold tabular-nums text-[#F05A0A]">{{ $myPerformance['activity'] }}</p><p class="text-[10px] font-medium text-slate-500">Activities</p></div>
        <div class="rounded-xl border border-white bg-white/80 p-3"><p class="text-lg font-bold tabular-nums text-[#F05A0A]">{{ $myPerformance['order_submitted'] }}</p><p class="text-[10px] font-medium text-slate-500">Orders sent</p></div>
        <div class="rounded-xl border border-white bg-white/80 p-3"><p class="text-lg font-bold tabular-nums text-[#F05A0A]">{{ $myPerformance['order_completed'] }}</p><p class="text-[10px] font-medium text-slate-500">Orders done</p></div>
    </div>

    <div class="mt-4 space-y-2">
        @forelse($performanceLeaderboard->take(6) as $entry)
            @php $displayName = explode(' ', trim($entry->user->name))[0]; @endphp
            <div class="flex items-center gap-3 rounded-xl border {{ $entry->user->id === auth()->id() ? 'border-orange-300 bg-orange-100/80' : 'border-white bg-white/80' }} px-3 py-2.5">
                <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $entry->rank <= 3 ? 'bg-[#FFB11B] text-[#381300]' : 'bg-slate-200 text-slate-700' }}">{{ $entry->rank }}</span>
                <span class="min-w-0 flex-1 truncate text-[13px] font-semibold text-slate-800">{{ $displayName }} @if($entry->user->id === auth()->id())<span class="font-medium text-[#F05A0A]">(You)</span>@endif</span>
                <span class="text-[13px] font-bold tabular-nums text-[#F05A0A]">{{ $entry->points }} pts</span>
            </div>
        @empty
            <p class="rounded-xl bg-white/80 px-4 py-5 text-center text-[12px] font-medium text-slate-500">The Hall of Fame begins with the first valid activity or order.</p>
        @endforelse
    </div>

    <p class="mt-3 text-[11px] leading-4 text-slate-500">Activity submitted +1 · Order submitted +3 · Order completed +5</p>
</section>
