@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Greeting> $greetings */
    $greetings = $greetings ?? \App\Models\Greeting::published()->get();
@endphp

@if($greetings->isNotEmpty())
<x-section-background :config="section_background('section_principal')"
                      background="var(--bg-alt)"
                      id="sambutan" class="py-20 sm:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @php
            $eyebrow  = setting('section_principal_eyebrow', 'Sambutan');
            $subtitle = setting('section_principal_subtitle', '');
        @endphp
        <div class="text-center mb-14" data-aos="fade-up">
            @if($eyebrow)
                <div class="fi-label mb-3">{{ $eyebrow }}</div>
            @endif
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight" style="color:var(--text)">
                {{ setting('section_principal_title') ?: 'Sambutan Para Tokoh' }}
            </h2>
            @if($subtitle)
                <p class="mt-3 text-base max-w-lg mx-auto leading-relaxed" style="color:var(--muted)">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @include('sections.bodies.greetings', ['greetings' => $greetings])
    </div>
</x-section-background>
@endif
