<section class="overflow-hidden rounded-2xl border border-orange-200 bg-gradient-to-br from-[#160604] via-[#2B0A05] to-[#6B1808] text-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-white/10 px-5 py-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#FFB11B]">Owner monitor</p>
            <h3 class="mt-1 text-xl font-bold tracking-tight">Performance Hall of Fame</h3>
            <p class="mt-1 text-[13px] text-white/70">Live recognition standings from submitted activities and orders.</p>
        </div>
        <div class="grid grid-cols-2 gap-2 sm:flex">
            <div class="rounded-xl border border-white/10 bg-black/25 px-4 py-2.5 text-center"><p class="text-xl font-black tabular-nums text-[#FFB11B]">{{ $performanceTotalPoints }}</p><p class="text-[10px] font-semibold uppercase tracking-wide text-white/60">Total points</p></div>
            <div class="rounded-xl border border-white/10 bg-black/25 px-4 py-2.5 text-center"><p class="text-xl font-black tabular-nums text-[#FFB11B]">{{ $performanceActiveParticipants }}</p><p class="text-[10px] font-semibold uppercase tracking-wide text-white/60">Active climbers</p></div>
        </div>
    </div>

    <div class="grid divide-y divide-white/10 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
        <div class="p-5">
            <div class="mb-3 flex items-center justify-between"><h4 class="text-[12px] font-bold uppercase tracking-[0.08em] text-white/75">Current standings</h4><span class="text-[11px] text-white/55">All time</span></div>
            <div class="space-y-2">
                @forelse($performanceLeaderboard->take(5) as $entry)
                    @php $displayName = explode(' ', trim($entry->user->name))[0]; @endphp
                    <div class="flex items-center gap-3 rounded-xl bg-white/10 px-3 py-2.5">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-black {{ $entry->rank <= 3 ? 'bg-[#FFB11B] text-[#281004]' : 'bg-white/15 text-white' }}">{{ $entry->rank }}</span>
                        <span class="min-w-0 flex-1 truncate text-[13px] font-semibold">{{ $displayName }}</span>
                        <span class="text-[13px] font-black tabular-nums text-[#FFB11B]">{{ $entry->points }} pts</span>
                    </div>
                @empty
                    <p class="rounded-xl bg-white/10 px-4 py-5 text-center text-[12px] text-white/65">No point activity has been recorded yet.</p>
                @endforelse
            </div>
        </div>

        <div class="p-5">
            <div class="mb-3 flex items-center justify-between"><h4 class="text-[12px] font-bold uppercase tracking-[0.08em] text-white/75">Latest movement</h4><span class="text-[11px] text-white/55">Last 6 updates</span></div>
            <div class="space-y-2">
                @forelse($recentPerformanceEntries as $entry)
                    @php $labels = ['activity_submitted' => 'Activity submitted', 'order_submitted' => 'Order submitted', 'order_completed' => 'Order completed']; @endphp
                    <div class="flex items-center gap-3 rounded-xl bg-black/20 px-3 py-2.5">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#F05A0A] text-xs font-black">+{{ $entry->points }}</div>
                        <div class="min-w-0 flex-1"><p class="truncate text-[13px] font-semibold">{{ $entry->user?->name ?? 'Former user' }}</p><p class="text-[11px] text-white/60">{{ $labels[$entry->event] ?? 'Point update' }} · {{ $entry->awarded_at?->diffForHumans() }}</p></div>
                    </div>
                @empty
                    <p class="rounded-xl bg-white/10 px-4 py-5 text-center text-[12px] text-white/65">Updates will appear here as the team submits valid work.</p>
                @endforelse
            </div>
        </div>
    </div>

    <p class="border-t border-white/10 px-5 py-3 text-[11px] text-white/55">Points are recognition only: activity +1 · order submitted +3 · order completed +5.</p>
</section>
