<div class="space-y-6">

    <!-- Header Actions -->
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm sm:flex sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:p-6">
        <div>
            <h2 class="text-base font-bold text-slate-900">Employee Accounts & Roles</h2>
            <p class="text-xs text-slate-500">Manage owner, manager, and staff system access</p>
        </div>

        <button 
            wire:click="$set('showUserModal', true)" 
            class="mt-4 min-h-[44px] w-full px-4 py-2.5 bg-amber-500 text-slate-950 font-bold text-sm rounded-xl shadow-sm transition-colors hover:bg-amber-600 sm:mt-0 sm:w-auto"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add New Employee
        </button>
    </div>

    <!-- Mobile account cards -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse($users as $u)
                <article class="space-y-3 p-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-800 text-sm font-bold text-white">
                            {{ strtoupper(substr($u->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-slate-900">{{ $u->name }}</p>
                            <p class="mt-0.5 break-all text-xs text-slate-500">{{ $u->email }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $u->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                            {{ ucfirst($u->status) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                        <span class="rounded-md px-2.5 py-1 text-xs font-bold uppercase tracking-wider {{ $u->role === 'owner' ? 'border border-amber-300 bg-amber-100 text-amber-900' : ($u->role === 'manager' ? 'border border-blue-300 bg-blue-100 text-blue-900' : 'bg-slate-100 text-slate-700') }}">
                            {{ $u->role }}
                        </span>
                        <button
                            type="button"
                            wire:click="toggleUserStatus({{ $u->id }})"
                            class="min-h-[40px] rounded-xl px-3 text-xs font-bold text-blue-700 transition-colors hover:bg-blue-50 hover:text-blue-900"
                        >
                            {{ $u->status === 'active' ? 'Deactivate' : 'Activate' }}
                        </button>
                    </div>
                </article>
            @empty
                <div class="px-6 py-12 text-center text-sm text-slate-500">
                    No employee accounts found.
                </div>
            @endforelse
        </div>

        <!-- Larger screens retain the denser management table. -->
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-[760px] w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Employee Name</th>
                        <th class="px-6 py-3.5">Email Address</th>
                        <th class="px-6 py-3.5">Role</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @foreach($users as $u)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-900 flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <span>{{ $u->name }}</span>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-700">
                                {{ $u->email }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-md uppercase tracking-wider
                                    {{ $u->role === 'owner' ? 'bg-amber-100 text-amber-900 border border-amber-300' : ($u->role === 'manager' ? 'bg-blue-100 text-blue-900 border border-blue-300' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $u->role }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 text-xs font-bold rounded-full {{ $u->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ ucfirst($u->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button 
                                    wire:click="toggleUserStatus({{ $u->id }})" 
                                    class="text-xs font-bold text-blue-600 hover:text-blue-800"
                                >
                                    {{ $u->status === 'active' ? 'Deactivate' : 'Activate' }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create User Modal -->
    @if($showUserModal)
        <template x-teleport="body">
        <div wire:click.self="$set('showUserModal', false)" class="fixed inset-0 z-[90] bg-black/30 flex items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="app-modal-sheet max-h-[92dvh] w-full space-y-5 overflow-y-auto rounded-t-2xl border border-slate-200 bg-white p-4 shadow-2xl sm:max-h-[88vh] sm:max-w-md sm:rounded-2xl sm:p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-900 text-base">👤 Add Employee Account</h3>
                    <button type="button" wire:click="$set('showUserModal', false)" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Close create employee dialog"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <form wire:submit.prevent="createUser" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Full Name *</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500" placeholder="John Doe">
                        @error('name') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Email Address *</label>
                        <input type="email" wire:model="email" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500" placeholder="john@alasclothing.com">
                        @error('email') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Password *</label>
                        <input type="password" wire:model="password" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500">
                        @error('password') <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">System Role *</label>
                        <select wire:model="role" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-amber-500">
                            <option value="staff">Staff (Fulfillment & daily tasks)</option>
                            <option value="manager">Manager (Inventory, Products & Operations)</option>
                            <option value="owner">Owner (Full system & Finance access)</option>
                        </select>
                    </div>

                    <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                        <button type="button" wire:click="$set('showUserModal', false)" class="min-h-[44px] rounded-xl bg-slate-100 px-4 py-2 text-xs font-bold text-slate-700">Cancel</button>
                        <button type="submit" class="min-h-[44px] rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 shadow hover:bg-amber-600">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
        </template>
    @endif

</div>
