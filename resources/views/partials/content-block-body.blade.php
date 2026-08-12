{{--
    Isi satu blok konten, apa pun jenisnya. Dipisah dari pembungkus seksinya
    (partials/content-blocks) agar blok yang sama bisa dirender baik sebagai
    seksi selebar layar maupun mengalir di dalam kartu artikel.
    Params:
      $block     — definisi satu blok
      $title     — teks alt/caption cadangan (judul halaman/artikel)
      $sectioned — blok dibungkus seksi sendiri; judul & labelnya sudah dirender
                   pembungkusnya, jadi jangan diulang di sini
--}}
@php
    $type = $block['type'] ?? '';
    $title = $title ?? '';
    $sectioned = $sectioned ?? false;
@endphp

    {{-- ── Teks ─────────────────────────────────────── --}}
    @if($type === 'rich_text' && filled(trim(strip_tags((string) ($block['content'] ?? '')))))
        <div class="block-prose">
            {!! $block['content'] !!}
        </div>
    @endif

    {{-- ── Cover Image ─────────────────────────────── --}}
    @if($type === 'image_cover' && !empty($block['image']))
        <figure class="block-cover">
            @unless($sectioned)<div class="block-label">Cover Image</div>@endunless
            <img src="{{ asset('storage/' . $block['image']) }}"
                 alt="{{ $block['caption'] ?? $title }}"
                 loading="lazy">
            @if(!empty($block['caption']))
                <figcaption>{{ $block['caption'] }}</figcaption>
            @endif
        </figure>
    @endif

    {{-- ── Carousel ─────────────────────────────────── --}}
    @if($type === 'image_carousel' && !empty($block['images']))
        @php $slides = array_values(array_filter($block['images'], fn($i) => !empty($i['image']))); @endphp
        @if(count($slides) > 0)
            <div class="my-8">
                @unless($sectioned)<div class="block-label">Carousel</div>@endunless
                <div class="block-carousel"
                     x-data="{
                         slide: 0,
                         total: {{ count($slides) }},
                         next() { this.slide = (this.slide + 1) % this.total },
                         prev() { this.slide = (this.slide - 1 + this.total) % this.total }
                     }">
                    @foreach($slides as $i => $img)
                        <div x-show="slide === {{ $i }}"
                             x-transition:enter="transition-opacity duration-500 ease-in-out"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition-opacity duration-300 ease-in-out"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="relative">
                            <img src="{{ asset('storage/' . $img['image']) }}"
                                 alt="{{ $img['caption'] ?? '' }}"
                                 loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                            @if(!empty($img['caption']))
                                <div class="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/65 to-transparent px-5 py-4">
                                    <p class="text-white text-sm leading-snug">{{ $img['caption'] }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @if(count($slides) > 1)
                        {{-- Prev / Next --}}
                        <button @click="prev()" class="carousel-btn" style="left:.75rem" aria-label="Sebelumnya">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button @click="next()" class="carousel-btn" style="right:.75rem" aria-label="Selanjutnya">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>

                        {{-- Dots --}}
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
                            @foreach($slides as $i => $img)
                                <button @click="slide = {{ $i }}"
                                        :class="slide === {{ $i }} ? 'w-5 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'"
                                        class="h-2 rounded-full transition-all duration-300"
                                        aria-label="Slide {{ $i + 1 }}">
                                </button>
                            @endforeach
                        </div>

                        {{-- Counter --}}
                        <div class="absolute top-3 right-3 text-xs font-semibold text-white bg-black/40 backdrop-blur-sm rounded-full px-2.5 py-1">
                            <span x-text="slide + 1"></span>/{{ count($slides) }}
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    {{-- ── Gallery ──────────────────────────────────── --}}
    @if($type === 'image_gallery' && !empty($block['images']))
        @php
            $imgs    = array_values(array_filter($block['images'], fn($i) => !empty($i['image'])));
            $cols    = $block['columns'] ?? '3';
            $gridCls = match($cols) {
                '2'     => 'grid-cols-2',
                '4'     => 'grid-cols-2 sm:grid-cols-4',
                default => 'grid-cols-2 sm:grid-cols-3',
            };
        @endphp
        @if(count($imgs) > 0)
            <div class="block-gallery"
                 x-data="{
                     lightbox: false,
                     current: '',
                     currentAlt: '',
                     open(src, alt) { this.current = src; this.currentAlt = alt; this.lightbox = true; },
                     close() { this.lightbox = false; }
                 }">
                @unless($sectioned)<div class="block-label">Galeri Foto</div>@endunless

                <div class="grid {{ $gridCls }} gap-3">
                    @foreach($imgs as $img)
                        <div class="gallery-item"
                             @click="open('{{ asset('storage/' . $img['image']) }}', '{{ $img['caption'] ?? '' }}')">
                            <img src="{{ asset('storage/' . $img['image']) }}"
                                 alt="{{ $img['caption'] ?? '' }}"
                                 loading="lazy">
                            @if(!empty($img['caption']))
                                <div class="gallery-caption">
                                    <p>{{ $img['caption'] }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Lightbox (teleported to <body> so `position: fixed` escapes any
                     transformed ancestor card and covers the full viewport) --}}
                <template x-teleport="body">
                    <div x-show="lightbox"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         @click.self="close()"
                         @keydown.escape.window="close()"
                         class="lightbox-overlay"
                         style="display: none;">
                        <img :src="current" :alt="currentAlt" class="lightbox-img">
                        <button @click="close()"
                                class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 border border-white/20 flex items-center justify-center text-white hover:bg-white/20 transition-colors"
                                aria-label="Tutup">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <p x-show="currentAlt" x-text="currentAlt"
                           class="absolute bottom-6 left-1/2 -translate-x-1/2 text-white/70 text-sm bg-black/40 backdrop-blur-sm rounded-full px-4 py-1.5 max-w-sm text-center"></p>
                    </div>
                </template>
            </div>
        @endif
    @endif

    {{-- ── CTA Button ───────────────────────────────── --}}
    @if($type === 'cta_button' && !empty($block['label']) && !empty($block['url']))
        @php
            $align   = $block['alignment'] ?? 'center';
            $justify = match($align) {
                'left'  => 'justify-start',
                'right' => 'justify-end',
                default => 'justify-center',
            };
            $styleCls = ($block['style'] ?? 'primary') === 'outline' ? 'block-cta-outline' : 'block-cta-primary';
            $newTab   = !empty($block['open_in_new_tab']);
            $color    = trim($block['color'] ?? '');
        @endphp
        <div class="block-cta flex {{ $justify }}">
            <a href="{{ $block['url'] }}"
               class="block-cta-btn {{ $styleCls }}"
               @if($color) style="--cta: {{ $color }}" @endif
               @if($newTab) target="_blank" rel="noopener noreferrer" @endif>
                {{ $block['label'] }}
            </a>
        </div>
    @endif

    {{-- ── Gambar & Teks ────────────────────────────── --}}
    @if($type === 'media_text')
        @php
            $mediaUrl   = icon_url($block['media_image'] ?? null);
            $imageFirst = ($block['media_position'] ?? 'right') === 'left';
            $heading    = trim((string) ($block['heading'] ?? ''));
            $body       = (string) ($block['text'] ?? '');
            $hasBody    = filled(trim(strip_tags($body)));
            $ctaLabel   = trim((string) ($block['cta_label'] ?? ''));
            $ctaUrl     = trim((string) ($block['cta_url'] ?? ''));
            $ctaNewTab  = !empty($block['cta_new_tab']);
            // Perataan berlaku ke judul, teks, dan tombol pada kolom teksnya
            $align      = in_array($block['text_align'] ?? 'left', ['left', 'center', 'right', 'justify'], true)
                ? $block['text_align'] ?? 'left'
                : 'left';
        @endphp
        {{-- Pada mode seksi, judulnya sudah dirender pembungkus — tanpa gambar
             dan teks, blok ini tidak menyisakan apa pun untuk digambar. --}}
        @if($mediaUrl || $hasBody || ($heading !== '' && ! $sectioned))
            <div class="block-media {{ $mediaUrl ? 'block-media-split' : '' }}">
                @if($mediaUrl)
                    <div class="{{ $imageFirst ? 'lg:order-1' : 'lg:order-2' }}">
                        <div class="fi-card overflow-hidden">
                            <img src="{{ $mediaUrl }}"
                                 alt="{{ $heading !== '' ? $heading : $title }}"
                                 loading="lazy"
                                 class="w-full object-cover aspect-4/3">
                        </div>
                    </div>
                @endif

                <div class="block-text-{{ $align }} {{ $mediaUrl ? ($imageFirst ? 'lg:order-2' : 'lg:order-1') : '' }}">
                    @if($heading !== '' && ! $sectioned)
                        <h3 class="block-media-title">{{ $heading }}</h3>
                    @endif

                    @if($hasBody)
                        <div class="block-prose">{!! $body !!}</div>
                    @endif

                    @if($ctaLabel !== '' && $ctaUrl !== '')
                        <div class="mt-6">
                            <a href="{{ $ctaUrl }}"
                               class="btn-primary"
                               @if($ctaNewTab) target="_blank" rel="noopener noreferrer" @endif>
                                {{ $ctaLabel }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    {{-- ── Deretan Kartu & Carousel Kartu ───────────── --}}
    @if(in_array($type, ['cards', 'cards_carousel'], true))
        @php
            $cards   = \App\Support\ContentCards::fromItems($block['items'] ?? []);
            $columns = max(1, min(4, (int) ($block['items_columns'] ?? 3) ?: 3));
            $isCarousel = $type === 'cards_carousel';
            $autoplay = $isCarousel && ($block['carousel_autoplay'] ?? true);
            $delayMs  = min(15, max(2, (int) ($block['carousel_autoplay_delay'] ?? 5) ?: 5)) * 1000;
            $loop     = (bool) ($block['carousel_loop'] ?? true);
            $pauseOnHover = $autoplay && ($block['carousel_pause_on_hover'] ?? true);
            $mediaRatio = \App\Models\ContentSection::cardRatioCss($block['items_ratio'] ?? null);
        @endphp
        @if($cards->isNotEmpty())
            @include('partials.content-cards-styles')

            <div class="cs-cards block-cards"
                 style="--cs-cols-sm:{{ min(2, $columns) }}; --cs-cols-lg:{{ $columns }}; --cs-media-ratio:{{ $mediaRatio }}">

                @if($isCarousel)
                    <div class="cs-carousel"
                         x-data="contentCarousel({
                             autoplay: {{ $autoplay ? 'true' : 'false' }},
                             delay: {{ $delayMs }},
                             loop: {{ $loop ? 'true' : 'false' }},
                             pauseOnHover: {{ $pauseOnHover ? 'true' : 'false' }},
                         })"
                         x-init="start()"
                         @mouseenter="hoverPause()"
                         @mouseleave="hoverResume()"
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

                        @if($block['carousel_arrows'] ?? true)
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

                        @if($block['carousel_dots'] ?? true)
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
                            <x-content-card :card="$card" />
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    @endif
