{{--
    Pembungkus <section> halaman depan: latar polos / abu / putih / gambar penuh
    dengan opsi blur, lapisan gelap, teks terang, dan parallax. Dipakai seksi
    bawaan (nilai dari setting) maupun seksi dinamis (nilai dari kolom record),
    keduanya lewat objek \App\Support\SectionBackground.

    Atribut lain (id, class, x-data, …) diteruskan apa adanya ke elemen <section>.
--}}
@props([
    /** @var \App\Support\SectionBackground */
    'config',
    /** Latar rancangan asli seksi, dipakai saat admin memilih "Bawaan Seksi". */
    'background' => null,
])

@php
    /** @var \App\Support\SectionBackground $config */
    $hasBackgroundImage = $config->hasImage();
    $usesParallax = $config->usesScrollParallax();
    $usesFixedBackground = $config->usesFixedBackground();
    $blurRadius = $config->blurRadius();
@endphp

@once
    <style>
        /*
            Lapisan latar dipasang di z-index negatif sehingga konten seksi
            tetap tampil di atasnya tanpa perlu dibungkus div berposisi —
            markup tiap seksi jadi tidak perlu diubah. Elemen <section>-nya
            sengaja dibiarkan transparan saat memakai gambar; warna dasarnya
            dipegang lapisan latar, sebab latar seksi sendiri dilukis di atas
            anak ber-z-index negatif. Dengan cara ini seksi juga tidak menjadi
            stacking context baru, jadi modal seperti lightbox galeri tetap
            bisa menutupi seluruh halaman.
        */
        .section-shell {
            position: relative;
        }

        .section-bg-layer {
            position: absolute;
            inset: 0;
            overflow: hidden;
            z-index: -1;
            background-color: #111827;
        }
        .section-bg {
            position: absolute;
            inset: 0;
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
        }
        .section-bg-parallax { will-change: transform; }
        .section-bg-overlay {
            position: absolute;
            inset: 0;
        }
        /* Lapisan gelap milik latar terkunci berdiri sendiri di bawah konten seksi */
        .section-bg-overlay-behind { z-index: -1; }

        /*
            Latar terkunci ke layar. `clip-path` pada seksi memotong anak
            position:fixed tanpa menjadikannya containing block — itulah yang
            membuat gambar diam sementara kontennya meluncur. Hindari transform,
            filter, atau will-change pada elemen <section> ini: ketiganya justru
            akan membuat latarnya ikut menggulir.
        */
        .section-clip { clip-path: inset(0); }
        .section-bg-fixed {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: -1;
            background-color: #111827;
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
        }
        /* Pakai viewport terbesar agar tidak menyisakan celah saat bilah alamat ponsel menyembunyi */
        @supports (height: 100lvh) {
            .section-bg-fixed { height: 100lvh; }
        }

        /*
            Teks terang di atas latar gambar gelap. Token warnanya yang ditimpa,
            bukan tiap elemennya, sehingga seluruh markup seksi yang memakai
            var(--text) / var(--muted) / .fi-card ikut menyesuaikan.
        */
        .section-light {
            --text: #ffffff;
            --muted: rgba(255,255,255,.82);
            --card: color-mix(in oklab, #111827 55%, transparent);
            --border: rgba(255,255,255,.22);
            --bg: transparent;
            --bg-alt: transparent;
            --primary: var(--primary-300);
            color: #fff;
        }
        /* Tombol tetap memakai warna aksen aslinya agar tidak pudar di atas latar gelap */
        .section-light .btn-primary { background: var(--primary-600); }
        .section-light .btn-primary:hover { background: var(--primary-700); }
        .section-light .btn-outline { color: #fff; }
    </style>
@endonce

<section {{ $attributes->class([
        'section-shell',
        'section-light' => $config->usesLightText(),
        'section-clip' => $usesFixedBackground,
    ])->merge(['style' => 'background:'.$config->style($background)]) }}>

    @if($usesFixedBackground)
        {{-- Latar terkunci ke layar: konten yang meluncur, gambarnya diam --}}
        <div class="section-bg-fixed"
             aria-hidden="true"
             style="background-image:url('{{ $config->imageUrl() }}');
                    @if($blurRadius > 0) filter:blur({{ $blurRadius }}px); transform:scale({{ $config->imageScale() }}); @endif"></div>

        @if($config->overlayOpacity() > 0)
            <div class="section-bg-overlay section-bg-overlay-behind" aria-hidden="true" style="background:{{ $config->overlayColor() }}"></div>
        @endif
    @elseif($hasBackgroundImage)
        <div class="section-bg-layer" aria-hidden="true">
            <div class="section-bg {{ $usesParallax ? 'section-bg-parallax' : '' }}"
                 style="background-image:url('{{ $config->imageUrl() }}');
                        @if($blurRadius > 0) filter:blur({{ $blurRadius }}px); @endif
                        transform:scale({{ $config->imageScale() }})"
                 @if($usesParallax)
                     {{-- Parallax: latar digeser lebih lambat dari konten saat digulir --}}
                     x-data="{
                         offset: 0,
                         scale: {{ $config->imageScale() }},
                         amplitude: {{ $config->parallaxAmplitude() }},
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

            @if($config->overlayOpacity() > 0)
                <div class="section-bg-overlay" style="background:{{ $config->overlayColor() }}"></div>
            @endif
        </div>
    @endif

    {{ $slot }}
</section>
