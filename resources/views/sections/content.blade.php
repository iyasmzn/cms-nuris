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
    /* ── Tipografi ───────────────────────────────────────────────
       Ukuran bawaan tiap elemen dikali `--cs-scale` yang dipasang
       admin lewat atribut style, sehingga ukuran responsifnya tetap
       dipegang CSS di sini. Perataan, ketebalan, dan warna cukup
       ditimpa inline karena tidak punya varian per lebar layar. */
    .cs-eyebrow { font-size: calc(.6875rem * var(--cs-scale, 1)); }

    .cs-title {
        font-size: calc(1.875rem * var(--cs-scale, 1));
        line-height: 1.15;
    }
    @media (min-width: 640px) {
        .cs-title { font-size: calc(2.25rem * var(--cs-scale, 1)); }
    }

    .content-section-prose {
        color: var(--muted);
        font-size: calc(1.0625rem * var(--cs-scale, 1));
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
    /* Warna kustom berlaku ke seluruh isi deskripsi, bukan hanya teks polosnya */
    .content-section-prose.cs-colored strong,
    .content-section-prose.cs-colored a { color: inherit; }
</style>

@include('partials.content-cards-styles')
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

    // Gaya huruf pilihan admin. Yang tidak diatur menghasilkan string kosong,
    // jadi elemennya tetap memakai gaya bawaan rancangan seksi.
    $typography = $contentSection->typography_styles;
    $hasDescription = $contentSection->has_description;

    // Warna judul bawaan berdiri di depan agar warna pilihan admin — bila ada —
    // menimpanya sebagai deklarasi terakhir.
    $titleStyle = implode(';', array_filter(['color:'.$headingColor, $typography->styleFor('title')]));
    $eyebrowStyle = $typography->styleFor('eyebrow');
    $descriptionStyle = $typography->styleFor('description');
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
                    <div class="fi-label cs-eyebrow mb-3" @if($eyebrowStyle) style="{{ $eyebrowStyle }}" @endif>{{ $contentSection->eyebrow }}</div>
                @endif

                <h2 class="cs-title font-extrabold tracking-tight {{ $hasDescription ? 'mb-5' : '' }}"
                    style="{{ $titleStyle }}">
                    {{ $contentSection->title }}
                </h2>

                @if($hasDescription)
                    <div class="content-section-prose{{ $typography->isColored('description') ? ' cs-colored' : '' }}"
                         @if($descriptionStyle) style="{{ $descriptionStyle }}" @endif>
                        {!! $contentSection->description !!}
                    </div>
                @endif

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
                                    <x-content-card :card="$card" :typography="$typography" />
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
                                <x-content-card :card="$card" :typography="$typography" />
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
