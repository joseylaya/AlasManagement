<div class="hidden md:block rounded-2xl border border-[#E8E8E8] bg-white p-5 space-y-4">
    <x-skeleton.element class="h-5 w-40" />
    @for($row = 0; $row < 6; $row++)
        <div class="grid grid-cols-6 gap-4 border-t border-[#F3F3F3] pt-4"><x-skeleton.element class="h-4" /><x-skeleton.element class="h-4" /><x-skeleton.element class="h-4" /><x-skeleton.element class="h-4 w-16" /><x-skeleton.element class="h-4" /><x-skeleton.element class="h-8 w-20 justify-self-end" /></div>
    @endfor
</div>
