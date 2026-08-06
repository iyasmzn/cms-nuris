{{--
    Lapisan media cover hero halaman & program: gambar, berkas video, atau video
    YouTube — pilihan yang sama dengan slide hero halaman depan. Diletakkan di
    belakang isi hero (breadcrumb, judul) yang tetap dirender pemanggilnya.

    Pop-up preview-nya berdiri sendiri di komponen ini, jadi hero mana pun bisa
    memakainya tanpa bergantung pada state Alpine milik halaman depan.
--}}
@props([
    /** @var \App\Support\PageHero */
    'hero',
    /** Gambar cadangan bila hero tidak punya gambar sendiri (mis. gambar program). */
    'poster' => null,
    /** Teks alt & judul iframe. */
    'title' => '',
])

@php
    /** @var \App\Support\PageHero $hero */
    $imageUrl = $hero->imageUrl() ?: $poster;
    $preview = $hero->hasVideoPreview() ? $hero->previewSource() : null;
@endphp

<div class="page-hero-media absolute inset-0 overflow-hidden"
     @if($preview)
         x-data="{
             video: null,
             open(source) { this.video = source; document.body.style.overflow = 'hidden' },
             close() { this.video = null; document.body.style.overflow = '' },
         }"
     @endif>

    @if($imageUrl)
        <img src="{{ $imageUrl }}"
             alt="{{ $title }}"
             class="absolute inset-0 w-full h-full object-cover">
    @endif

    @if($hero->mediaType === \App\Models\Slide::MEDIA_VIDEO && $hero->hasVideoBackground())
        <video class="absolute inset-0 w-full h-full object-cover"
               @if($imageUrl) poster="{{ $imageUrl }}" @endif
               muted loop playsinline autoplay preload="metadata">
            <source src="{{ $hero->videoFileUrl() }}">
        </video>
    @endif

    @if($hero->mediaType === \App\Models\Slide::MEDIA_YOUTUBE && $hero->hasVideoBackground())
        <iframe class="page-hero-yt"
                src="{{ $hero->youtubeBackgroundSrc() }}"
                title="{{ $title }}"
                allow="autoplay; encrypted-media; picture-in-picture"
                referrerpolicy="strict-origin-when-cross-origin"
                tabindex="-1"
                aria-hidden="true"></iframe>
    @endif

    {{-- Lapisan gelap agar teks hero tetap terbaca di atas media apa pun --}}
    <div class="absolute inset-0 pointer-events-none"
         style="background:linear-gradient(135deg,rgba(0,0,0,.75) 0%,rgba(0,0,0,.5) 55%,rgba(0,0,0,.25) 100%)"></div>

    @if($preview)
        @unless($hero->showsVideoButton())
            {{-- Tanpa tombol, area covernya sendiri yang membuka pop-up --}}
            <button type="button"
                    class="absolute inset-0 w-full h-full cursor-pointer"
                    @click="open({{ Illuminate\Support\Js::from($preview) }})">
                <span class="sr-only">Putar video: {{ $title }}</span>
            </button>
        @else
            <button type="button"
                    @click="open({{ Illuminate\Support\Js::from($preview) }})"
                    class="page-hero-play absolute z-20 inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-white/10 border border-white/25 backdrop-blur-sm font-semibold text-white text-sm hover:bg-white/20 transition-all">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5.14v13.72c0 .78.85 1.26 1.52.86l11.14-6.86a1 1 0 000-1.72L9.52 4.28A1 1 0 008 5.14z"/></svg>
                {{ $hero->videoButtonText() }}
            </button>
        @endunless

        {{-- Pop-up pemutar video (bersuara, dengan kontrol) --}}
        <template x-if="video">
            <div class="fixed inset-0 z-100 flex items-center justify-center bg-black/85 backdrop-blur-sm p-4"
                 @click.self="close()"
                 @keydown.escape.window="close()">
                <div class="relative w-full max-w-4xl">
                    <button type="button"
                            @click="close()"
                            class="absolute -top-11 right-0 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 text-white flex items-center justify-center transition-all">
                        <span class="sr-only">Tutup video</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>

                    <div class="relative w-full overflow-hidden rounded-2xl bg-black shadow-2xl"
                         :style="'aspect-ratio:' + video.ratio">
                        <template x-if="video.type === 'embed'">
                            <iframe :src="video.src"
                                    class="absolute inset-0 w-full h-full"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen></iframe>
                        </template>
                        <template x-if="video.type === 'file'">
                            <video :src="video.src"
                                   class="absolute inset-0 w-full h-full"
                                   controls autoplay playsinline></video>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>

@once
    <style>
        /* Iframe YouTube 16:9 diperbesar agar selalu menutup penuh area hero */
        .page-hero-yt {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: max(100%, 177.78vh);
            height: max(100%, 56.25vw);
            min-width: 100%;
            min-height: 100%;
            border: 0;
            pointer-events: none;
        }
        .page-hero-play { right: 1.5rem; bottom: 1.5rem; }
    </style>
@endonce
