<x-section-background :config="section_background('section_programs')"
                      background="var(--bg)"
                      id="program" class="py-20 sm:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        @php
            $eyebrow  = setting('section_programs_eyebrow', 'Keunggulan Kami');
            $subtitle = setting('section_programs_subtitle', 'Berbagai program yang dirancang untuk membentuk santri berprestasi dan berakhlak mulia.');
        @endphp
        <div class="text-center mb-14" data-aos="fade-up">
            @if($eyebrow)
                <div class="fi-label mb-3">{{ $eyebrow }}</div>
            @endif
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight" style="color:var(--text)">
                {{ setting('section_programs_title') ?: 'Program Unggulan' }}
            </h2>
            @if($subtitle)
                <p class="mt-3 text-base max-w-lg mx-auto leading-relaxed" style="color:var(--muted)">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @if(isset($programs) && $programs->isNotEmpty())
        @include('sections.bodies.programs', ['programs' => $programs])

        <div class="mt-12 text-center">
            <a href="{{ route('programs.index') }}" class="btn-outline">Lihat Semua Program</a>
        </div>

        @else
        <div class="text-center py-16 fi-card">
            <div class="text-5xl mb-4">🎓</div>
            <p class="text-base font-medium" style="color:var(--muted)">Program segera tersedia.</p>
        </div>
        @endif

    </div>
</x-section-background>
