{{--
    Seksi dinamis halaman depan: kartu gambar (kiri/kanan) berdampingan dengan
    judul, deskripsi, dan tombol CTA. Latarnya — polos / abu / gambar penuh
    dengan blur, lapisan gelap, dan parallax — ditangani komponen
    <x-section-background> yang sama dengan seksi bawaan. Di-include per record
    oleh welcome.blade.
--}}
@isset($contentSection)
@once
<style>
    .content-section-prose {
        color: var(--muted);
        font-size: 1.0625rem;
        line-height: 1.8;
    }
    .content-section-prose > *:first-child { margin-top: 0; }
    .content-section-prose > *:last-child { margin-bottom: 0; }
    .content-section-prose p { margin: 0 0 1rem; }
    .content-section-prose strong { color: var(--text); font-weight: 700; }
    .content-section-prose a { color: var(--primary); font-weight: 600; text-decoration: underline; text-underline-offset: 3px; }
    .content-section-prose ul,
    .content-section-prose ol { margin: 0 0 1rem; padding-left: 1.25rem; }
    .content-section-prose ul { list-style: disc; }
    .content-section-prose ol { list-style: decimal; }
    .content-section-prose li { margin-bottom: .4rem; }
</style>
@endonce

@php
    /** @var \App\Models\ContentSection $contentSection */
    $hasImage = filled($contentSection->image_url);
    $imageFirst = $contentSection->image_position === 'left';

    // Warna judul mengikuti pilihan teks terang milik seksi berlatar gambar.
    $headingColor = $contentSection->uses_light_text ? '#ffffff' : 'var(--text)';
@endphp

<x-section-background :config="\App\Support\SectionBackground::forContentSection($contentSection)"
                      id="{{ $contentSection->anchor_id }}"
                      class="overflow-hidden py-20 sm:py-28">

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="grid gap-10 lg:gap-16 items-center {{ $hasImage ? 'lg:grid-cols-2' : 'max-w-3xl mx-auto text-center' }}">

            @if($hasImage)
                {{-- Kartu gambar — pada layar kecil selalu tampil di atas teks --}}
                <div class="{{ $imageFirst ? 'lg:order-1' : 'lg:order-2' }}"
                     data-aos="{{ $imageFirst ? 'fade-right' : 'fade-left' }}">
                    <div class="fi-card overflow-hidden">
                        <img src="{{ $contentSection->image_url }}"
                             alt="{{ $contentSection->title }}"
                             loading="lazy"
                             class="w-full object-cover aspect-4/3">
                    </div>
                </div>
            @endif

            <div class="{{ $hasImage ? ($imageFirst ? 'lg:order-2' : 'lg:order-1') : '' }}"
                 data-aos="fade-up">

                @if($contentSection->eyebrow)
                    <div class="fi-label mb-3">{{ $contentSection->eyebrow }}</div>
                @endif

                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight mb-5" style="color:{{ $headingColor }}">
                    {{ $contentSection->title }}
                </h2>

                <div class="content-section-prose">
                    {!! $contentSection->description !!}
                </div>

                @if($contentSection->has_cta)
                    <div class="mt-8 {{ $hasImage ? '' : 'flex justify-center' }}">
                        <a href="{{ $contentSection->cta_url }}"
                           class="btn-primary"
                           @if($contentSection->cta_new_tab) target="_blank" rel="noopener noreferrer" @endif>
                            {{ $contentSection->cta_label }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-section-background>
@endisset
