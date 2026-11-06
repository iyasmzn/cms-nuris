@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Faq> $faqs */
    $faqs = $faqs ?? \App\Models\Faq::published()->get();
@endphp

@if($faqs->isNotEmpty())
<x-section-background :config="section_background('section_faq')"
                      background="var(--bg-alt, var(--bg))"
                      id="faq" class="py-20 sm:py-28">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        @php
            $eyebrow  = setting('section_faq_eyebrow', 'Pertanyaan Umum');
            $subtitle = setting('section_faq_subtitle', 'Jawaban atas pertanyaan yang paling sering diajukan calon santri dan orang tua.');
        @endphp
        <div class="text-center mb-12" data-aos="fade-up">
            @if($eyebrow)
                <div class="fi-label mb-3">{{ $eyebrow }}</div>
            @endif
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight" style="color:var(--text)">
                {{ setting('section_faq_title') ?: 'Ada yang Ingin Ditanyakan?' }}
            </h2>
            @if($subtitle)
                <p class="mt-3 text-base max-w-lg mx-auto leading-relaxed" style="color:var(--muted)">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @include('sections.bodies.faq', ['faqs' => $faqs])
    </div>
</x-section-background>
@endif
