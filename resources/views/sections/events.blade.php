<x-section-background :config="section_background('section_events')"
                      background="var(--bg-alt)"
                      id="kegiatan-upcoming" class="py-20 sm:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        @php
            $eyebrow  = setting('section_events_eyebrow', 'Agenda Pesantren');
            $subtitle = setting('section_events_subtitle', 'Pengajian, seminar, dan berbagai kegiatan menarik yang segera diselenggarakan.');
        @endphp
        <div class="flex items-end justify-between gap-6 mb-14" data-aos="fade-up">
            <div>
                @if($eyebrow)
                    <div class="fi-label mb-3">{{ $eyebrow }}</div>
                @endif
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight" style="color:var(--text)">
                    {{ setting('section_events_title') ?: 'Kegiatan Akan Datang' }}
                </h2>
                @if($subtitle)
                    <p class="mt-2 text-base max-w-md leading-relaxed" style="color:var(--muted)">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>
            <a href="{{ route('events.index') }}"
               class="shrink-0 inline-flex items-center gap-1.5 text-sm font-semibold transition-colors hover:opacity-75"
               style="color:var(--primary)">
                Semua Kegiatan
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        @if(isset($upcomingEvents) && $upcomingEvents->isNotEmpty())
        @include('sections.bodies.events', ['events' => $upcomingEvents])
        @else
        <div class="text-center py-16 fi-card">
            <div class="text-5xl mb-4">🗓</div>
            <p class="text-base font-medium" style="color:var(--muted)">Belum ada kegiatan yang akan datang.</p>
            <a href="{{ route('events.index', ['filter' => 'past']) }}" class="btn-outline mt-4 inline-flex">Lihat Kegiatan Sebelumnya</a>
        </div>
        @endif

    </div>
</x-section-background>
