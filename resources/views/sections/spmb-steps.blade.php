@php
    $spmbYear   = spmb_year_label();
    $stepsTitle = setting('spmb_steps_title', 'Tahapan SPMB');
    $stepsDesc  = setting('spmb_steps_description', 'Ikuti langkah-langkah berikut untuk mendaftarkan diri sebagai calon peserta didik baru.');
@endphp

<x-section-background :config="section_background('section_spmb_steps')"
                      background="var(--bg)"
                      id="tahapan-spmb"
                      class="py-20 sm:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14" data-aos="fade-up">
            <div class="fi-label mb-3">Cara Mendaftar</div>
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight" style="color:var(--text)">
                {{ $stepsTitle }} {{ $spmbYear }}
            </h2>
            <p class="mt-3 text-base max-w-xl mx-auto leading-relaxed" style="color:var(--muted)">{{ $stepsDesc }}</p>
        </div>

        @include('sections.bodies.spmb-steps', ['procedures' => \App\Support\LandingBlocks::spmbProcedures()])
    </div>
</x-section-background>
