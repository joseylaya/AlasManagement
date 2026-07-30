<div class="space-y-3 md:hidden">
    @for($card = 0; $card < 4; $card++)
        <div class="rounded-2xl border border-[#E8E8E8] bg-white p-4 space-y-3"><div class="flex justify-between"><x-skeleton.element class="h-4 w-28" /><x-skeleton.element class="h-5 w-16" /></div><x-skeleton.element class="h-5 w-3/5" /><x-skeleton.element class="h-4 w-2/5" /><div class="flex justify-between pt-2"><x-skeleton.element class="h-5 w-20" /><x-skeleton.element class="h-9 w-24" /></div></div>
    @endfor
</div>
