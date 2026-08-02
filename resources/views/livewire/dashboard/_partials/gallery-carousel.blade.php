@if($dashboardBanners->isNotEmpty())
    <section
        x-data="{
            active: 0,
            total: {{ $dashboardBanners->count() }},
            timer: null,
            touchStart: 0,
            next() { this.active = (this.active + 1) % this.total },
            previous() { this.active = (this.active - 1 + this.total) % this.total },
            swipe(end) {
                if (Math.abs(end - this.touchStart) < 40) return;
                end < this.touchStart ? this.next() : this.previous();
            },
            init() { if (this.total > 1) this.timer = setInterval(() => this.next(), 6000) },
            destroy() { clearInterval(this.timer) }
        }"
        class="overflow-hidden rounded-2xl border border-[#E3E3E3] bg-white shadow-sm"
        aria-label="Clothing design gallery"
        @touchstart.passive="touchStart = $event.touches[0].clientX"
        @touchend="swipe($event.changedTouches[0].clientX)"
    >
        <div class="relative aspect-[16/8] min-h-[200px] bg-white sm:min-h-[260px]">
            @foreach($dashboardBanners as $index => $banner)
                <article x-show="active === {{ $index }}" x-transition.opacity.duration.500ms class="absolute inset-0" @if($index > 0) x-cloak @endif>
                    <img src="{{ asset('storage/'.$banner->image_path) }}" alt="{{ $banner->title ?: 'Clothing design' }}" class="h-full w-full object-cover drop-shadow-[0_12px_16px_rgba(0,0,0,0.18)]" loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                </article>
            @endforeach

            @if($dashboardBanners->count() > 1)
                <button type="button" @click="previous()" class="absolute left-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white backdrop-blur transition hover:bg-black/60" aria-label="Previous gallery image">‹</button>
                <button type="button" @click="next()" class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white backdrop-blur transition hover:bg-black/60" aria-label="Next gallery image">›</button>
                <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5">
                    @foreach($dashboardBanners as $index => $banner)
                        <button type="button" @click="active = {{ $index }}" :class="active === {{ $index }} ? 'w-5 bg-slate-900' : 'w-1.5 bg-slate-500/50'" class="h-1.5 rounded-full transition-all" aria-label="Show gallery image {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
