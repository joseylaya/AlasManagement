<div class="space-y-6">

    <!-- Header & Filter -->
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm sm:p-6 sm:flex sm:flex-row sm:items-center sm:justify-between sm:gap-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">{{ auth()->user()->isStaff() ? 'My Activity History' : 'Immutable System Audit Trail' }}</h2>
            <p class="text-xs text-slate-500">{{ auth()->user()->isStaff() ? 'Your product edits, order updates, and recorded actions' : 'Every product edit, order status update, and cash movement is logged permanently' }}</p>
        </div>

        <div class="mt-4 grid w-full grid-cols-1 gap-3 sm:mt-0 sm:w-[34rem] sm:grid-cols-2">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search action or log detail..." 
                class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white"
            >

            @unless(auth()->user()->isStaff())
                <select wire:model.live="selectedUser" class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:bg-white">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ ucfirst($u->role) }})</option>
                    @endforeach
                </select>
            @endunless
        </div>
    </div>

    <!-- Mobile activity feed -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse($logs as $log)
                <article class="space-y-3 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-900">{{ $log->user->name ?? 'System Automated' }}</p>
                            @unless(auth()->user()->isStaff())
                                <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $log->user->role ?? 'System' }}</p>
                            @endunless
                        </div>
                        <time class="shrink-0 text-right font-mono text-[10px] leading-relaxed text-slate-400" datetime="{{ $log->created_at->toIso8601String() }}">
                            {{ $log->created_at->format('M d, Y') }}<br>
                            {{ $log->created_at->format('g:i:s A') }}
                        </time>
                    </div>

                    <span class="inline-flex max-w-full rounded-md border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800">
                        <span class="truncate">{{ $log->action }}</span>
                    </span>

                    <p class="break-words text-xs leading-relaxed text-slate-700">{{ $log->description }}</p>

                    <p class="font-mono text-[10px] text-slate-400">IP · {{ $log->ip_address ?? '127.0.0.1' }}</p>
                </article>
            @empty
                <div class="px-6 py-12 text-center text-sm text-slate-500">
                    No activity logs recorded yet.
                </div>
            @endforelse
        </div>

        <!-- Larger screens retain the denser audit table. -->
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-[760px] w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Timestamp</th>
                        <th class="px-6 py-3.5">User Accountability</th>
                        <th class="px-6 py-3.5">Action Executed</th>
                        <th class="px-6 py-3.5">Details & Description</th>
                        <th class="px-6 py-3.5 text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-xs font-mono text-slate-600 whitespace-nowrap">
                                {{ $log->created_at->format('M d, Y g:i:s A') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">{{ $log->user->name ?? 'System Automated' }}</div>
                                @unless(auth()->user()->isStaff())
                                    <div class="text-[10px] text-slate-400 font-semibold uppercase">{{ $log->user->role ?? 'System' }}</div>
                                @endunless
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 rounded-md">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-800">
                                {{ $log->description }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-xs text-slate-400">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                No activity logs recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
