{{--
    Seksi dinamis halaman depan. Isinya salah satu dari tiga bentuk: gambar
    berdampingan teks, deretan kartu, atau kartu berjalan (carousel). Latarnya —
    polos / abu / gambar penuh dengan blur, lapisan gelap, dan parallax —
    ditangani komponen <x-section-background> yang sama dengan seksi bawaan.
    Di-include per record oleh welcome.blade.
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

    /* ── Kartu ───────────────────────────────────────────────────
       Jumlah kartu sebaris diatur admin lewat --cs-cols-lg; layar
       kecil selalu satu kartu, layar sedang paling banyak dua. */
    .cs-cards {
        --cs-gap: 1.25rem;
        --cs-cols: 1;
    }
    @media (min-width: 640px) { .cs-cards { --cs-cols: var(--cs-cols-sm); } }
    @media (min-width: 1024px) { .cs-cards { --cs-cols: var(--cs-cols-lg); } }

    .cs-grid {
        display: grid;
        gap: var(--cs-gap);
        grid-template-columns: repeat(var(--cs-cols), minmax(0, 1fr));
    }

    .cs-card {
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }
    .cs-card-media {
        aspect-ratio: 4 / 3;
        overflow: hidden;
        background: color-mix(in oklab, var(--text) 6%, transparent);
    }
    .cs-card-media img { transition: transform .6s ease; }
    .cs-card:hover .cs-card-media img { transform: scale(1.05); }
    .cs-card-body {
        display: flex;
        flex-direction: column;
        gap: .5rem;
        flex: 1;
        padding: 1.5rem;
    }
    .cs-card-title {
        font-size: 1.0625rem;
        font-weight: 700;
        line-height: 1.4;
        color: var(--text);
    }
    .cs-card-text {
        font-size: .9375rem;
        line-height: 1.7;
        color: var(--muted);
    }
    .cs-card-cta {
        margin-top: auto;
        padding-top: .5rem;
        display: inline-flex;
        align-items: center;
        gap: .375rem;
        font-size: .875rem;
        font-weight: 600;
        color: var(--primary);
        /* Di atas tautan kartu agar tetap bisa diklik sendiri */
        position: relative;
        z-index: 2;
    }
    .cs-card-cta:hover { text-decoration: underline; text-underline-offset: 3px; }
    /* Tautan direntangkan menutupi kartu tanpa label tombol */
    .cs-card-link {
        position: absolute;
        inset: 0;
        z-index: 1;
        border-radius: inherit;
    }
    .cs-card:has(.cs-card-link) { cursor: pointer; }

    /* ── Carousel ────────────────────────────────────────────────
       Memakai kontainer scroll asli + scroll-snap: usap di ponsel
       jalan apa adanya, tombol & titik hanya menggulirkannya. */
    .cs-carousel { position: relative; }
    .cs-track {
        display: flex;
        gap: var(--cs-gap);
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        scroll-behavior: smooth;
        overscroll-behavior-x: contain;
        scrollbar-width: none;
        -ms-overflow-style: none;
        /* Ruang agar bayangan & efek angkat kartu tidak terpotong */
        padding: .5rem .25rem 1.75rem;
    }
    .cs-track::-webkit-scrollbar { display: none; }
    .cs-slide {
        flex: 0 0 calc((100% - (var(--cs-gap) * (var(--cs-cols) - 1))) / var(--cs-cols));
        scroll-snap-align: start;
    }

    .cs-arrow {
        position: absolute;
        top: calc(50% - 1.75rem);
        display: none;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 9999px;
        background: var(--card, #fff);
        border: 1px solid var(--border);
        color: var(--text);
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
        transition: transform .2s ease, opacity .2s ease;
        z-index: 2;
    }
    @media (min-width: 1024px) { .cs-arrow { display: flex; } }
    .cs-arrow:hover { transform: scale(1.08); }
    .cs-arrow[disabled] { opacity: .35; cursor: default; transform: none; }
    .cs-arrow-prev { left: -1rem; }
    .cs-arrow-next { right: -1rem; }

    .cs-dots {
        display: flex;
        justify-content: center;
        gap: .5rem;
        margin-top: .25rem;
    }
    .cs-dot {
        width: .5rem;
        height: .5rem;
        border-radius: 9999px;
        background: color-mix(in oklab, var(--text) 25%, transparent);
        transition: width .25s ease, background .25s ease;
    }
    .cs-dot.is-active {
        width: 1.5rem;
        background: var(--primary);
    }

    @media (prefers-reduced-motion: reduce) {
        .cs-track { scroll-behavior: auto; }
        .cs-card-media img { transition: none; }
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('contentCarousel', (config) => ({
            autoplay: config.autoplay ?? false,
            delay: config.delay ?? 5000,
            loop: config.loop ?? true,
            pages: 1,
            page: 0,
            pageWidth: 0,
            paused: false,
            timer: null,

            start() {
                this.measure()

                this.onResize = () => this.measure()
                window.addEventListener('resize', this.onResize, { passive: true })

                if (! this.autoplay || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return
                }

                this.timer = setInterval(() => {
                    if (! this.paused) {
                        this.next()
                    }
                }, this.delay)
            },

            destroy() {
                clearInterval(this.timer)
                window.removeEventListener('resize', this.onResize)
            },

            /**
             * Jumlah kartu yang muat dihitung dari lebar kartu pertama, bukan
             * dari pengaturan admin, supaya titik navigasinya tetap benar di
             * layar kecil yang hanya menampilkan satu kartu.
             */
            measure() {
                const track = this.$refs.track
                const slide = track.firstElementChild

                if (! slide || track.clientWidth === 0) {
                    this.pages = 1

                    return
                }

                const gap = parseFloat(getComputedStyle(track).columnGap) || 0
                const step = slide.offsetWidth + gap
                const perView = Math.max(1, Math.round((track.clientWidth + gap) / step))

                this.pageWidth = perView * step
                this.pages = Math.max(1, Math.ceil(track.children.length / perView))
                this.page = Math.min(this.pages - 1, Math.round(track.scrollLeft / this.pageWidth))
            },

            onScroll() {
                if (this.pageWidth <= 0) {
                    return
                }

                this.page = Math.min(this.pages - 1, Math.max(0, Math.round(this.$refs.track.scrollLeft / this.pageWidth)))
            },

            goTo(page) {
                this.$refs.track.scrollTo({ left: page * this.pageWidth })
            },

            next() {
                if (this.page >= this.pages - 1) {
                    if (this.loop) {
                        this.goTo(0)
                    }

                    return
                }

                this.goTo(this.page + 1)
            },

            previous() {
                if (this.page <= 0) {
                    if (this.loop) {
                        this.goTo(this.pages - 1)
                    }

                    return
                }

                this.goTo(this.page - 1)
            },

            pause() { this.paused = true },
            resume() { this.paused = false },
        }))
    })
</script>
@endonce

@php
    /** @var \App\Models\ContentSection $contentSection */
    $showsMedia = $contentSection->shows_media;
    $hasImage = $showsMedia && filled($contentSection->image_url);
    $imageFirst = $contentSection->image_position === 'left';

    $cards = $contentSection->cards;
    $usesCards = $contentSection->uses_cards;
    $usesCarousel = $contentSection->uses_carousel;

    // Judul & deskripsi berdiri sendiri di atas kartu; pada tata letak gambar
    // ia tetap berdampingan dengan gambarnya seperti sebelumnya.
    $headerIsStacked = ! $showsMedia;

    // Warna judul mengikuti pilihan teks terang milik seksi berlatar gambar.
    $headingColor = $contentSection->uses_light_text ? '#ffffff' : 'var(--text)';
@endphp

<x-section-background :config="\App\Support\SectionBackground::forContentSection($contentSection)"
                      id="{{ $contentSection->anchor_id }}"
                      class="overflow-hidden py-20 sm:py-28">

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="grid gap-10 lg:gap-16 items-center {{ $hasImage ? 'lg:grid-cols-2' : ($headerIsStacked ? '' : 'max-w-3xl mx-auto text-center') }}">

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

            <div class="{{ $hasImage ? ($imageFirst ? 'lg:order-2' : 'lg:order-1') : ($headerIsStacked ? 'max-w-3xl mx-auto text-center' : '') }}"
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

                @if($contentSection->has_cta && ! $headerIsStacked)
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

        @if($usesCards)
            <div class="cs-cards mt-14"
                 style="--cs-cols-sm:{{ min(2, $contentSection->card_columns) }}; --cs-cols-lg:{{ $contentSection->card_columns }}">

                @if($usesCarousel)
                    <div class="cs-carousel"
                         x-data="contentCarousel({
                             autoplay: {{ $contentSection->uses_autoplay ? 'true' : 'false' }},
                             delay: {{ $contentSection->autoplay_delay_ms }},
                             loop: {{ $contentSection->carousel_loop ? 'true' : 'false' }},
                         })"
                         x-init="start()"
                         @mouseenter="pause()"
                         @mouseleave="resume()"
                         @focusin="pause()"
                         @focusout="resume()"
                         @touchstart.passive="pause()">

                        <div class="cs-track" x-ref="track" @scroll.passive="onScroll()">
                            @foreach($cards as $card)
                                <div class="cs-slide">
                                    <x-content-card :card="$card" />
                                </div>
                            @endforeach
                        </div>

                        @if($contentSection->carousel_arrows)
                            <button type="button" class="cs-arrow cs-arrow-prev"
                                    x-show="pages > 1"
                                    @click="previous()"
                                    :disabled="! loop && page === 0"
                                    aria-label="Kartu sebelumnya">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>

                            <button type="button" class="cs-arrow cs-arrow-next"
                                    x-show="pages > 1"
                                    @click="next()"
                                    :disabled="! loop && page >= pages - 1"
                                    aria-label="Kartu berikutnya">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        @endif

                        @if($contentSection->carousel_dots)
                            <div class="cs-dots" x-show="pages > 1">
                                <template x-for="index in pages" :key="index">
                                    <button type="button"
                                            class="cs-dot"
                                            :class="{ 'is-active': index - 1 === page }"
                                            @click="goTo(index - 1)"
                                            :aria-label="`Ke halaman kartu ${index}`"></button>
                                </template>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="cs-grid">
                        @foreach($cards as $card)
                            <div data-aos="fade-up" data-aos-delay="{{ ($loop->index % 3) * 80 }}">
                                <x-content-card :card="$card" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if($contentSection->has_cta && $headerIsStacked)
            <div class="mt-12 flex justify-center">
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
</x-section-background>
@endisset
