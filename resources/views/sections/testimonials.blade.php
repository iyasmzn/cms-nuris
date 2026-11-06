@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Testimonial> $testimonials */
    $testimonials = $testimonials ?? \App\Models\Testimonial::published()->get();
@endphp

@if($testimonials->isNotEmpty())
<x-section-background :config="section_background('section_testimonials')"
                      background="var(--bg)"
                      id="kesan-pesan" class="py-20 sm:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @php
            $eyebrow  = setting('section_testimonials_eyebrow', 'Kesan & Pesan');
            $subtitle = setting('section_testimonials_subtitle', 'Cerita dan harapan dari para alumni yang telah menempuh pendidikan bersama kami.');
        @endphp
        <div class="text-center mb-14" data-aos="fade-up">
            @if($eyebrow)
                <div class="fi-label mb-3">{{ $eyebrow }}</div>
            @endif
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight" style="color:var(--text)">
                {{ setting('section_testimonials_title') ?: 'Apa Kata Alumni' }}
            </h2>
            @if($subtitle)
                <p class="mt-3 text-base max-w-lg mx-auto leading-relaxed" style="color:var(--muted)">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @include('sections.bodies.testimonials', ['testimonials' => $testimonials])
    </div>
</x-section-background>
@endif
