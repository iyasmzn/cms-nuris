{{--
    Seksi dinamis halaman depan: kartu gambar (kiri/kanan) berdampingan dengan
    judul, deskripsi, dan tombol CTA, di atas latar polos / abu / gambar penuh
    (opsional blur, lapisan gelap, dan parallax). Di-include per record oleh
    welcome.blade.
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

    /* Varian teks terang untuk seksi berlatar gambar gelap */
    .content-section-light .content-section-prose { color: rgba(255,255,255,.88); }
    .content-section-light .content-section-prose strong { color: #fff; }
    .content-section-light .content-section-prose a { color: #fff; }
    .content-section-light .fi-label { color: rgba(255,255,255,.9); }

    .content-section-bg {
        position: absolute;
        inset: 0;
        background-position: center;
        background-size: cover;
        background-repeat: no-repeat;
    }
    .content-section-bg-parallax { will-change: transform; }

    /*
        Latar terkunci ke layar. `clip-path` pada seksi memotong anak
        position:fixed tanpa menjadikannya containing block — itulah yang
        membuat gambar diam sementara kontennya meluncur. Hindari transform,
        filter, atau will-change pada elemen <section> ini: ketiganya justru
        akan membuat latarnya ikut menggulir.
    */
    .content-section-clip { clip-path: inset(0); }
    .content-section-bg-fixed {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        background-position: center;
        background-size: cover;
        background-repeat: no-repeat;
    }
    /* Pakai viewport terbesar agar tidak menyisakan celah saat bilah alamat ponsel menyembunyi */
    @supports (height: 100lvh) {
        .content-section-bg-fixed { height: 100lvh; }
    }
</style>
@endonce

@php
    /** @var \App\Models\ContentSection $contentSection */
    $hasImage = filled($contentSection->image_url);
    $imageFirst = $contentSection->image_position === 'left';

    $hasBackgroundImage = $contentSection->has_background_image;
    $usesParallax = $contentSection->uses_scroll_parallax;
    $usesFixedBackground = $contentSection->uses_fixed_background;
    $isLightText = $contentSection->uses_light_text;

    // Blur & parallax dikompensasi dengan scale agar tepi gambar tidak bocor.
    // Pada parallax, separuh sisa ruang hasil scale itulah jarak geser maksimalnya,
    // sehingga gambar tetap menutup seksi di posisi gulir mana pun. Latar terkunci
    // tidak perlu diperbesar karena ia memang tidak bergerak.
    $parallaxFactor = $contentSection->parallax_factor;
    $backgroundScale = match (true) {
        $usesParallax => round(1 + $parallaxFactor, 3),
        $contentSection->background_blur > 0 => 1.1,
        default => 1,
    };
    $parallaxAmplitude = round($parallaxFactor / 2, 3);

    $sectionBackground = match (true) {
        $hasBackgroundImage => '#111827',
        $contentSection->background === 'alt' => 'var(--bg-alt, var(--bg))',
        default => 'var(--bg)',
    };

    $headingColor = $isLightText ? '#ffffff' : 'var(--text)';
@endphp

<section id="{{ $contentSection->anchor_id }}"
         class="relative overflow-hidden py-20 sm:py-28 {{ $isLightText ? 'content-section-light' : '' }} {{ $usesFixedBackground ? 'content-section-clip' : '' }}"
         style="background:{{ $sectionBackground }}">

    @if($usesFixedBackground)
        {{-- Latar terkunci ke layar: konten yang meluncur, gambarnya diam --}}
        <div class="content-section-bg-fixed"
             aria-hidden="true"
             style="background-image:url('{{ $contentSection->background_image_url }}');
                    @if($contentSection->background_blur > 0) filter:blur({{ $contentSection->background_blur }}px); transform:scale({{ $backgroundScale }}); @endif"></div>

        @if($contentSection->overlay_opacity > 0)
            <div class="absolute inset-0" aria-hidden="true" style="background:rgba(17,24,39,{{ $contentSection->overlay_opacity }})"></div>
        @endif
    @elseif($hasBackgroundImage)
        <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="content-section-bg {{ $usesParallax ? 'content-section-bg-parallax' : '' }}"
                 style="background-image:url('{{ $contentSection->background_image_url }}');
                        @if($contentSection->background_blur > 0) filter:blur({{ $contentSection->background_blur }}px); @endif
                        transform:scale({{ $backgroundScale }})"
                 @if($usesParallax)
                     {{-- Parallax: latar digeser lebih lambat dari konten saat digulir --}}
                     x-data="{
                         offset: 0,
                         scale: {{ $backgroundScale }},
                         amplitude: {{ $parallaxAmplitude }},
                         ticking: false,
                         update() {
                             if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                                 this.offset = 0;
                                 return;
                             }

                             const rect = this.$el.getBoundingClientRect();
                             const travel = (window.innerHeight + rect.height) / 2;

                             if (travel <= 0) {
                                 return;
                             }

                             const distance = window.innerHeight / 2 - (rect.top + rect.height / 2);
                             const progress = Math.max(-1, Math.min(1, distance / travel));

                             this.offset = progress * rect.height * this.amplitude;
                         },
                         schedule() {
                             if (this.ticking) {
                                 return;
                             }

                             this.ticking = true;
                             requestAnimationFrame(() => { this.ticking = false; this.update() });
                         },
                     }"
                     x-init="update(); window.addEventListener('load', () => update())"
                     x-on:scroll.window.passive="schedule()"
                     x-on:resize.window.passive="schedule()"
                     x-bind:style="{ transform: `translate3d(0, ${offset}px, 0) scale(${scale})` }"
                 @endif></div>

            @if($contentSection->overlay_opacity > 0)
                <div class="absolute inset-0" style="background:rgba(17,24,39,{{ $contentSection->overlay_opacity }})"></div>
            @endif
        </div>
    @endif

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
</section>
@endisset
