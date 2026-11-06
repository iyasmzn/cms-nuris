@if($contactItems->isNotEmpty())
<x-section-background :config="section_background('section_contact')"
                     background="linear-gradient(135deg,#0a0a0b 0%,#1a1a1e 50%,#0a0f1a 100%)"
                     id="kontak-section" class="py-24 sm:py-32">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        @php
            $eyebrow  = setting('section_contact_eyebrow', 'Hubungi Kami');
            $subtitle = setting('section_contact_subtitle', 'Punya pertanyaan seputar SPMB, akademik, atau kegiatan sekolah? Jangan ragu untuk menghubungi kami.');
        @endphp
        <div class="text-center mb-16" data-aos="fade-up">
            @if($eyebrow)
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border text-xs font-bold uppercase tracking-widest mb-5"
                     style="background:rgba(217,119,6,.12);border-color:rgba(217,119,6,.3);color:var(--primary-300)">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse inline-block"></span>
                    {{ $eyebrow }}
                </div>
            @endif
            <h2 class="text-3xl sm:text-5xl font-extrabold text-white leading-tight tracking-tight mb-4">
                {{ setting('section_contact_title') ?: 'Kami Siap Membantu Anda' }}
            </h2>
            @if($subtitle)
                <p class="text-white/50 text-base max-w-xl mx-auto leading-relaxed">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @include('sections.bodies.contact', ['contactItems' => $contactItems])
    </div>
</x-section-background>
@endif
