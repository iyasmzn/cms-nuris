@php
    $heroSlider ??= \App\Support\HeroSlider::fromSettings();
@endphp

{{-- Ukuran latar video mengikuti tinggi hero per breakpoint (h-130/145/160)
     sehingga iframe 16:9 selalu menutup penuh tanpa gepeng. --}}
<style>
    .hero-media { --hero-h: 32.5rem; }
    @media (min-width: 640px)  { .hero-media { --hero-h: 36.25rem; } }
    @media (min-width: 1024px) { .hero-media { --hero-h: 40rem; } }

    .hero-yt-frame {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: max(100vw, calc(var(--hero-h) * 16 / 9));
        height: max(56.25vw, var(--hero-h));
        border: 0;
        pointer-events: none;
    }

    /* ── Perpindahan slide ────────────────────────────────────────
       Slide yang keluar (.is-leaving) bergerak berlawanan arah dengan
       slide yang masuk, arahnya (--hero-dir) ditentukan tombol/dot
       yang ditekan sehingga geraknya terasa maju-mundur. */
    .hero-slide {
        opacity: 0;
        z-index: 0;
        transition: opacity var(--hero-duration, 700ms) ease,
                    transform var(--hero-duration, 700ms) ease;
    }
    .hero-slide.is-active  { opacity: 1; z-index: 10; }
    .hero-slide.is-leaving { z-index: 5; }

    .hero-anim-slide .hero-slide            { transform: translateX(calc(var(--hero-dir, 1) * 100%)); }
    .hero-anim-slide .hero-slide.is-leaving { transform: translateX(calc(var(--hero-dir, 1) * -100%)); }

    .hero-anim-slide-vertical .hero-slide            { transform: translateY(calc(var(--hero-dir, 1) * 100%)); }
    .hero-anim-slide-vertical .hero-slide.is-leaving { transform: translateY(calc(var(--hero-dir, 1) * -100%)); }

    .hero-anim-zoom .hero-slide            { transform: scale(1.12); }
    .hero-anim-zoom .hero-slide.is-leaving { transform: scale(.92); }

    .hero-anim-slide .hero-slide.is-active,
    .hero-anim-slide-vertical .hero-slide.is-active,
    .hero-anim-zoom .hero-slide.is-active { transform: none; }

    @media (prefers-reduced-motion: reduce) {
        .hero-slide { transition-duration: .01ms; transform: none !important; }
    }
</style>

<section id="beranda"
         class="hero-media hero-anim-{{ $heroSlider->transition }} relative h-130 sm:h-145 lg:h-160 overflow-hidden -mt-17"
         style="--hero-duration: {{ $heroSlider->duration }}ms"
         :style="{ '--hero-dir': slideDir }"
         @if($heroSlider->pauseOnHover)
             {{-- Perangkat sentuh ikut memicu mouseenter tanpa selalu mengirim
                  mouseleave, jadi jeda hanya berlaku bila kursor sungguh ada. --}}
             @mouseenter="heroPaused = matchMedia('(hover: hover)').matches"
             @mouseleave="heroPaused = false"
             @focusin="heroPaused = true"
             @focusout="heroPaused = false"
         @endif>

    {{-- Slides --}}
    @forelse($slides as $index => $s)
        {{-- Slide pertama sudah aktif dari server agar hero tidak kosong
             selama Alpine belum sempat dimuat. --}}
        <div class="hero-slide absolute inset-0 @if($index === 0) is-active @endif"
             :class="{
                 'is-active': {{ $index }} === slide,
                 'is-leaving': {{ $index }} === prevSlide && {{ $index }} !== slide,
             }">

            {{-- Background image — juga jadi poster/fallback untuk slide video --}}
            <img src="{{ $s->image_url }}"
                 alt="{{ $s->title }}"
                 class="absolute inset-0 w-full h-full object-cover"
                 loading="{{ $index === 0 ? 'eager' : 'lazy' }}">

            {{-- Background video: file unggahan, diputar hanya saat slide aktif --}}
            @if($s->media_type === \App\Models\Slide::MEDIA_VIDEO && $s->hasVideoBackground())
                <video class="absolute inset-0 w-full h-full object-cover"
                       poster="{{ $s->image_url }}"
                       muted loop playsinline autoplay preload="metadata"
                       x-effect="{{ $index }} === slide ? $el.play().catch(() => {}) : $el.pause()">
                    <source src="{{ $s->video_file_url }}">
                </video>
            @endif

            {{-- Background video: YouTube, mute & berulang (syarat autoplay browser) --}}
            @if($s->media_type === \App\Models\Slide::MEDIA_YOUTUBE && $s->hasVideoBackground())
                <div class="absolute inset-0 overflow-hidden">
                    <iframe class="hero-yt-frame"
                            :src="{{ $index }} === slide ? {{ Illuminate\Support\Js::from($s->youtube_background_src) }} : ''"
                            title="{{ $s->title }}"
                            allow="autoplay; encrypted-media; picture-in-picture"
                            referrerpolicy="strict-origin-when-cross-origin"
                            tabindex="-1"
                            aria-hidden="true"></iframe>
                </div>
            @endif

            {{-- Refined gradient overlay --}}
            <div class="absolute inset-0 pointer-events-none"
                 style="background:linear-gradient(135deg,rgba(0,0,0,.7) 0%,rgba(0,0,0,.4) 55%,rgba(0,0,0,.1) 100%)"></div>

            {{-- Tanpa tombol preview, area video sendiri yang membuka pop-up --}}
            @if($s->hasVideoPreview() && ! $s->showsVideoButton())
                <button type="button"
                        class="absolute inset-0 z-5 w-full h-full cursor-pointer"
                        @click="openVideo({{ Illuminate\Support\Js::from($s->previewSource()) }})">
                    <span class="sr-only">Putar video: {{ $s->title }}</span>
                </button>
            @endif

            {{-- Content --}}
            <div class="relative z-10 h-full flex items-center pointer-events-none">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                    <div class="max-w-2xl text-white pointer-events-auto">

                        {{-- Slide counter pill: tak ada yang perlu dihitung kalau slide-nya tunggal --}}
                        @if($slides->count() > 1)
                            <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 backdrop-blur-md px-4 py-1.5 rounded-full text-sm mb-6">
                                <span class="w-2 h-2 rounded-full animate-pulse" style="background:var(--primary-400)"></span>
                                <span class="text-white/80 text-xs font-semibold tracking-wider">{{ $index + 1 }} / {{ $slides->count() }}</span>
                            </div>
                        @endif

                        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.08] tracking-tight mb-5">
                            {{ $s->title }}
                        </h1>

                        @if($s->subtitle)
                            <p class="text-white/75 text-base sm:text-lg leading-relaxed mb-8 max-w-xl font-light">
                                {{ $s->subtitle }}
                            </p>
                        @endif

                        <div class="flex flex-wrap gap-3">
                            @if($s->button_label && $s->button_url)
                                <a href="{{ $s->button_url }}"
                                   class="inline-flex items-center gap-2 px-7 py-3.5 rounded-2xl font-bold hover:opacity-90 transition-all shadow-xl text-sm"
                                   style="background:var(--primary-400);color:var(--primary-900)">
                                    {{ $s->button_label }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </a>
                            @endif

                            @if($s->showsVideoButton())
                                <button type="button"
                                        @click="openVideo({{ Illuminate\Support\Js::from($s->previewSource()) }})"
                                        class="inline-flex items-center gap-2 px-7 py-3.5 rounded-2xl bg-white/10 border border-white/25 backdrop-blur-sm font-semibold hover:bg-white/20 transition-all text-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5.14v13.72c0 .78.85 1.26 1.52.86l11.14-6.86a1 1 0 000-1.72L9.52 4.28A1 1 0 008 5.14z"/></svg>
                                    {{ $s->video_button_text }}
                                </button>
                            @endif

                            <a href="#profil"
                               class="inline-flex items-center gap-2 px-7 py-3.5 rounded-2xl bg-white/10 border border-white/25 backdrop-blur-sm font-semibold hover:bg-white/20 transition-all text-sm">
                                Profil Sekolah
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        {{-- Fallback --}}
        <div class="absolute inset-0 flex items-center justify-center"
             style="background:linear-gradient(135deg,#1d1d1f 0%,#3a3a3c 100%)">
            <div class="text-center text-white px-6">
                <h1 class="text-5xl font-extrabold mb-4 tracking-tight">{{ setting('site_name', config('app.name')) }}</h1>
                <p class="text-white/60 text-lg font-light">{{ setting('site_tagline', 'Unggul, Berkarakter, Berprestasi') }}</p>
            </div>
        </div>
    @endforelse

    {{-- Dot indicators --}}
    @if($slides->count() > 1)
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-20 flex gap-2">
            @foreach($slides as $index => $s)
                <button @click="goToSlide({{ $index }})"
                        class="transition-all duration-300 rounded-full"
                        :class="{{ $index }} === slide
                            ? 'w-7 h-2.5 opacity-100'
                            : 'w-2.5 h-2.5 bg-white/40 hover:bg-white/70'"
                        :style="{{ $index }} === slide ? 'background:var(--primary-400)' : ''"></button>
            @endforeach
        </div>
    @endif

    {{-- Prev / Next arrows --}}
    @if($slides->count() > 1)
        <button @click="stepSlide(-1)"
                class="absolute left-5 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-black/25 hover:bg-black/45 backdrop-blur-sm text-white flex items-center justify-center transition-all hover:scale-110">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button @click="stepSlide(1)"
                class="absolute right-5 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-black/25 hover:bg-black/45 backdrop-blur-sm text-white flex items-center justify-center transition-all hover:scale-110">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
        </button>
    @endif

    {{-- Bottom wave --}}
    <div class="absolute bottom-0 inset-x-0 z-10 pointer-events-none">
        <svg viewBox="0 0 1440 56" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" class="w-full h-14">
            <path d="M0 56L80 48C160 40 320 24 480 22.7C640 21.3 800 34.7 960 40C1120 45.3 1280 42.7 1360 41.3L1440 40V56H0Z" fill="var(--bg)"/>
        </svg>
    </div>
</section>

{{-- Pop-up pemutar video (bersuara, dengan kontrol) --}}
<template x-if="videoModal">
    <div class="fixed inset-0 z-100 flex items-center justify-center bg-black/85 backdrop-blur-sm p-4"
         @click.self="closeVideo()"
         @keydown.escape.window="closeVideo()">
        <div class="relative w-full max-w-4xl">
            <button type="button"
                    @click="closeVideo()"
                    class="absolute -top-11 right-0 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 text-white flex items-center justify-center transition-all">
                <span class="sr-only">Tutup video</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="relative w-full overflow-hidden rounded-2xl bg-black shadow-2xl"
                 :style="'aspect-ratio:' + videoModal.ratio">
                <template x-if="videoModal.type === 'embed'">
                    <iframe :src="videoModal.src"
                            class="absolute inset-0 w-full h-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen></iframe>
                </template>
                <template x-if="videoModal.type === 'file'">
                    <video :src="videoModal.src"
                           class="absolute inset-0 w-full h-full"
                           controls autoplay playsinline></video>
                </template>
            </div>
        </div>
    </div>
</template>
