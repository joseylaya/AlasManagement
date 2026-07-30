<div class="space-y-5" aria-hidden="true">
    <div class="flex items-center justify-between"><div class="space-y-2"><x-skeleton.element class="h-6 w-44" /><x-skeleton.element class="h-4 w-64" /></div><x-skeleton.element class="h-10 w-28" /></div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3"><x-skeleton.element class="h-24" /><x-skeleton.element class="h-24" /><x-skeleton.element class="h-24" /><x-skeleton.element class="h-24 {{ auth()->user()?->isStaff() ? 'hidden' : '' }}" /></div>
    <x-skeleton.mobile-card-list />
    <x-skeleton.table />
</div>
