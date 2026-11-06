@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\AlumniStat> $alumniStats */
    $alumniStats = $alumniStats ?? \App\Models\AlumniStat::ordered()->get();

    /** @var \Illuminate\Support\Collection<int, \App\Models\AlumniUniversity> $alumniUniversities */
    $alumniUniversities = $alumniUniversities ?? \App\Models\AlumniUniversity::active()->get();

    /** @var \App\Support\AlumniMarquee $alumniMarquee */
    $alumniMarquee = $alumniMarquee ?? \App\Support\AlumniMarquee::fromSettings();
@endphp

@if($alumniStats->isNotEmpty() || $alumniUniversities->isNotEmpty())
<x-section-background :config="section_background('section_alumni')"
                     background="var(--bg-alt, var(--bg))"
                     id="alumni" class="py-20 sm:py-28 overflow-hidden">

    {{-- Soft accent glow, keeps the section distinct from the plain stats strip --}}
    <div aria-hidden="true" class="alumni-glow"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">

        @php
            $eyebrow  = setting('section_alumni_eyebrow', 'Jejak Alumni');
            $subtitle = setting('section_alumni_subtitle', 'Lulusan kami melanjutkan studi dan berkarya di berbagai perguruan tinggi dalam dan luar negeri.');
        @endphp
        <div class="text-center mb-14" data-aos="fade-up">
            @if($eyebrow)
                <div class="fi-label mb-3">{{ $eyebrow }}</div>
            @endif
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight" style="color:var(--text)">
                {{ setting('section_alumni_title') ?: 'Ke Mana Alumni Kami Melangkah' }}
            </h2>
            @if($subtitle)
                <p class="mt-3 text-base max-w-xl mx-auto leading-relaxed" style="color:var(--muted)">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @include('sections.bodies.alumni-stats', ['alumniStats' => $alumniStats])
    </div>

    @include('sections.bodies.alumni-logos', ['alumniUniversities' => $alumniUniversities, 'alumniMarquee' => $alumniMarquee])

    @include('sections.bodies.alumni-styles')
</x-section-background>
@endif
