@once
<style>
    .stat-card { position: relative; }
    /* Tautan menutupi seluruh kartu; radiusnya ikut kartu agar fokus keyboard rapi */
    .stat-card-link {
        position: absolute;
        inset: 0;
        z-index: 1;
        border-radius: inherit;
    }
    .stat-card:has(.stat-card-link) { cursor: pointer; }
</style>
@endonce

<x-section-background :config="section_background('section_stats')"
                      background="transparent"
                      id="profil" class="py-16 sm:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($stats->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-{{ min($stats->count(), 4) }} gap-4 sm:gap-5">
                @foreach($stats as $stat)
                    <div class="fi-card fi-card-hover stat-card p-7 sm:p-8 text-center"
                         data-aos="zoom-in" data-aos-delay="{{ $loop->index * 80 }}">

                        {{-- Tautan direntangkan menutupi kartu agar seluruh kartunya bisa diklik,
                             tanpa mengubah susunan isi kartu. --}}
                        @if($stat->has_link)
                            <a href="{{ $stat->url }}"
                               class="stat-card-link"
                               @if($stat->link_opens_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>
                                <span class="sr-only">{{ $stat->label }}</span>
                            </a>
                        @endif

                        @if($url = icon_url($stat->icon_image))
                            <img src="{{ $url }}" alt="{{ $stat->label }}" loading="lazy"
                                 class="w-10 h-10 mx-auto mb-3 object-contain">
                        @else
                            <div class="text-4xl mb-3">{{ $stat->icon }}</div>
                        @endif
                        <div class="text-3xl sm:text-4xl font-black tracking-tight" style="color:var(--primary)">
                            <x-count-up :value="$stat->value" />
                        </div>
                        <div class="text-sm font-semibold mt-2" style="color:var(--text)">{{ $stat->label }}</div>
                        @if($stat->sub)
                            <div class="text-xs mt-1 leading-relaxed" style="color:var(--muted)">{{ $stat->sub }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-section-background>
