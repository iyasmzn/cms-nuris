@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\AlumniStat> $alumniStats */
    $alumniStats = $alumniStats ?? \App\Models\AlumniStat::ordered()->get();

    /** @var \Illuminate\Support\Collection<int, \App\Models\AlumniUniversity> $alumniUniversities */
    $alumniUniversities = $alumniUniversities ?? \App\Models\AlumniUniversity::active()->get();
@endphp

@if($alumniStats->isNotEmpty() || $alumniUniversities->isNotEmpty())
<x-section-background :config="section_background('section_alumni')"
                     background="var(--bg-alt, var(--bg))"
                     id="alumni" class="py-20 sm:py-28 overflow-hidden">

    {{-- Soft accent glow, keeps the section distinct from the plain stats strip --}}
    <div aria-hidden="true" class="alumni-glow"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">

        @php
            $eyebrow  = setting('section_alumni_eyebrow', 'Jejak Alumni');
            $subtitle = setting('section_alumni_subtitle', 'Lulusan kami melanjutkan studi dan berkarya di berbagai perguruan tinggi dalam dan luar negeri.');
        @endphp
        <div class="text-center mb-14" data-aos="fade-up">
            @if($eyebrow)
                <div class="fi-label mb-3">{{ $eyebrow }}</div>
            @endif
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight" style="color:var(--text)">
                {{ setting('section_alumni_title') ?: 'Ke Mana Alumni Kami Melangkah' }}
            </h2>
            @if($subtitle)
                <p class="mt-3 text-base max-w-xl mx-auto leading-relaxed" style="color:var(--muted)">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        {{-- ── Kartu capaian alumni ────────────────────────────────
             Gaya sengaja berbeda dari seksi Statistik: kartu mendatar
             dengan pita aksen di sisi kiri dan ikon berbingkai. --}}
        @if($alumniStats->isNotEmpty())
            <div class="grid gap-4 sm:gap-5 sm:grid-cols-2 lg:grid-cols-{{ min(max($alumniStats->count(), 1), 4) }}">
                @foreach($alumniStats as $alumniStat)
                    <div class="alumni-stat" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                        <span class="alumni-stat-bar" aria-hidden="true"></span>

                        <div class="alumni-stat-icon">
                            @if($url = icon_url($alumniStat->icon_image))
                                <img src="{{ $url }}" alt="{{ $alumniStat->label }}" loading="lazy"
                                     class="w-8 h-8 object-contain">
                            @else
                                <span class="text-2xl leading-none">{{ $alumniStat->icon }}</span>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <div class="alumni-stat-value"><x-count-up :value="$alumniStat->value" /></div>
                            <div class="alumni-stat-label">{{ $alumniStat->label }}</div>
                            @if($alumniStat->sub)
                                <div class="alumni-stat-sub">{{ $alumniStat->sub }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── Logo kampus — berjalan otomatis, bisa digeser/diusap ──
         Memakai kontainer scroll asli sehingga swipe di ponsel jalan
         apa adanya; rAF menambah scrollLeft dan berhenti saat disentuh. --}}
    @if($alumniUniversities->isNotEmpty())
        {{-- Kartu dicetak sekali saja; penggandaan untuk gulir tak berujung
             dikerjakan di sisi klien, hanya bila barisnya memang lebih panjang
             dari layar. Bila muat seluruhnya, barisnya diam & rata tengah. --}}
        <div class="mt-14 sm:mt-16" data-aos="fade-up">
            <p class="text-center text-xs font-semibold tracking-wider uppercase mb-6" style="color:var(--muted)">
                {{ setting('section_alumni_logos_title') ?: 'Kampus Tujuan Alumni' }}
            </p>

            <div class="alumni-marquee-wrap">
                <div class="alumni-marquee"
                     role="list"
                     aria-label="Logo kampus tujuan alumni"
                     x-data="{
                         speed: 0.3,
                         /* Posisi disimpan sendiri sebagai pecahan: browser membulatkan
                            scrollLeft ke piksel penuh, jadi kecepatan di bawah 1px per
                            frame akan hilang bila dibaca balik dari elemen. */
                         offset: 0,
                         /* Lebar satu putaran penuh, yaitu lebar seluruh kartu asli. */
                         cycle: 0,
                         /* Baris berjalan hanya bila kartunya melebihi lebar layar. */
                         rolling: false,
                         originals: {{ $alumniUniversities->count() }},
                         paused: false,
                         dragging: false,
                         lastX: 0,
                         resumeTimer: null,
                         resizeTimer: null,
                         /* Diukur dari kartunya sendiri, bukan scrollWidth, sebab margin
                            kartu terakhir tidak selalu ikut terhitung di scrollWidth. */
                         trackWidth() {
                             return [...this.$el.children].slice(0, this.originals).reduce(
                                 (total, item) => total + item.offsetWidth + (parseFloat(getComputedStyle(item).marginRight) || 0),
                                 0,
                             )
                         },
                         /** Buang klon lama, ukur ulang, lalu gandakan seperlunya. */
                         build() {
                             const el = this.$el
                             while (el.children.length > this.originals) { el.lastElementChild.remove() }
                             el.scrollLeft = 0
                             this.offset = 0

                             this.cycle = this.trackWidth()
                             this.rolling = this.cycle > el.clientWidth + 1

                             if (! this.rolling) return

                             /* Digandakan sampai ada cukup kartu untuk mengisi layar
                                melewati titik lompat, supaya sambungannya tak terlihat. */
                             const source = [...el.children]
                             let guard = 12
                             while (el.scrollWidth < this.cycle + el.clientWidth && guard--) {
                                 source.forEach(item => {
                                     const clone = item.cloneNode(true)
                                     clone.setAttribute('aria-hidden', 'true')
                                     clone.setAttribute('tabindex', '-1')
                                     el.appendChild(clone)
                                 })
                             }
                         },
                         /** Kembalikan posisi ke putaran pertama supaya gulirnya tak berujung. */
                         wrap(value) {
                             if (this.cycle <= 0) return 0
                             if (value >= this.cycle) return value - this.cycle
                             if (value < 0) return value + this.cycle
                             return value
                         },
                         move(value) {
                             this.offset = this.wrap(value)
                             this.$el.scrollLeft = this.offset
                         },
                         hold() {
                             this.paused = true
                             clearTimeout(this.resumeTimer)
                         },
                         release(delay = 1200) {
                             clearTimeout(this.resumeTimer)
                             this.resumeTimer = setTimeout(() => this.paused = false, delay)
                         },
                         start() {
                             this.build()

                             /* Muat/tidaknya baris bergantung lebar layar, jadi diukur
                                ulang setiap kali jendelanya berubah ukuran. */
                             window.addEventListener('resize', () => {
                                 clearTimeout(this.resizeTimer)
                                 this.resizeTimer = setTimeout(() => this.build(), 200)
                             }, { passive: true })

                             if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return

                             const step = () => {
                                 if (this.rolling && ! this.paused && ! this.dragging) {
                                     this.move(this.offset + this.speed)
                                 }
                                 requestAnimationFrame(step)
                             }
                             requestAnimationFrame(step)
                         },
                         onPointerDown(event) {
                             if (event.pointerType !== 'mouse') return
                             this.dragging = true
                             this.lastX = event.clientX
                             this.$el.setPointerCapture(event.pointerId)
                         },
                         onPointerMove(event) {
                             if (! this.dragging) return
                             event.preventDefault()
                             const delta = event.clientX - this.lastX
                             this.lastX = event.clientX
                             this.move(this.offset - delta)
                         },
                         onPointerUp(event) {
                             if (! this.dragging) return
                             this.dragging = false
                             if (this.$el.hasPointerCapture?.(event.pointerId)) {
                                 this.$el.releasePointerCapture(event.pointerId)
                             }
                             this.release()
                         },
                         /* Hanya gulir dari pengguna (sentuh / roda) yang disinkronkan;
                            gulir hasil rAF diabaikan agar pecahan tadi tidak terbuang. */
                         onScroll() {
                             if (! this.rolling || ! this.paused || this.dragging) return
                             if (this.$el.scrollLeft >= this.cycle) { this.$el.scrollLeft -= this.cycle }
                             else if (this.$el.scrollLeft <= 0) { this.$el.scrollLeft += this.cycle }
                             this.offset = this.$el.scrollLeft
                         },
                     }"
                     x-init="start()"
                     :class="{ 'is-dragging': dragging, 'is-static': ! rolling }"
                     @pointerdown="onPointerDown($event)"
                     @pointermove="onPointerMove($event)"
                     @pointerup="onPointerUp($event)"
                     @pointercancel="onPointerUp($event)"
                     @pointerleave="onPointerUp($event)"
                     @mouseenter="hold()"
                     @mouseleave="release(0)"
                     @touchstart.passive="hold()"
                     @touchend.passive="release()"
                     @scroll.passive="onScroll()">

                    @foreach($alumniUniversities as $university)
                        @php
                            $logoUrl = $university->logo_url;
                        @endphp
                        <a class="alumni-logo"
                           role="listitem"
                           title="{{ $university->name }}"
                           @if($university->url) href="{{ $university->url }}" target="_blank" rel="noopener noreferrer" @endif>
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo {{ $university->name }}" loading="lazy" draggable="false">
                            @else
                                <span class="alumni-logo-fallback">{{ $university->initials }}</span>
                            @endif
                            <span class="alumni-logo-name">{{ $university->name }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <style>
        .alumni-glow {
            position: absolute;
            top: -6rem; left: 50%;
            width: 46rem; height: 26rem;
            transform: translateX(-50%);
            background: radial-gradient(closest-side, color-mix(in oklab, var(--primary) 14%, transparent), transparent);
            pointer-events: none;
        }

        /* ── Kartu capaian alumni ───────────────────────────────── */
        .alumni-stat {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem 1.5rem 1.5rem 1.75rem;
            overflow: hidden;
            border-radius: 1.25rem;
            border: 1px solid var(--border);
            background: linear-gradient(135deg, var(--primary-50) 0%, var(--card, #fff) 55%);
            transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
        }
        .alumni-stat:hover {
            transform: translateY(-3px);
            border-color: var(--primary-200);
            box-shadow: 0 18px 44px rgba(0,0,0,.10);
        }
        .alumni-stat-bar {
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 5px;
            background: linear-gradient(180deg, var(--primary), color-mix(in oklab, var(--primary) 45%, white));
        }
        .alumni-stat-icon {
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            width: 3.25rem; height: 3.25rem;
            border-radius: 1rem;
            background: var(--card, #fff);
            border: 1px solid var(--primary-100);
            box-shadow: 0 4px 14px rgba(0,0,0,.06);
        }
        .alumni-stat-value {
            font-size: 1.875rem;
            line-height: 1.1;
            font-weight: 900;
            letter-spacing: -.02em;
            color: var(--primary);
        }
        .alumni-stat-label {
            margin-top: .25rem;
            font-size: .875rem;
            font-weight: 700;
            color: var(--text);
        }
        .alumni-stat-sub {
            margin-top: .125rem;
            font-size: .75rem;
            line-height: 1.5;
            color: var(--muted);
        }

        /* ── Baris logo kampus ──────────────────────────────────── */
        .alumni-marquee-wrap {
            position: relative;
            /* Ujung kiri-kanan memudar agar logo tidak terpotong kasar */
            -webkit-mask-image: linear-gradient(90deg, transparent, #000 6rem, #000 calc(100% - 6rem), transparent);
            mask-image: linear-gradient(90deg, transparent, #000 6rem, #000 calc(100% - 6rem), transparent);
        }
        .alumni-marquee {
            display: flex;
            /* Jarak antar-logo memakai margin (bukan gap) & tanpa padding samping
               agar separuh scrollWidth persis sama dengan satu putaran penuh —
               syarat supaya sambungan gulirnya tidak meloncat. */
            /* Padding atas–bawah memberi ruang untuk bayangan kartu & efek
               angkat saat hover, supaya tidak terpotong oleh overflow. */
            padding: 1rem 0 2.25rem;
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: none;
            -ms-overflow-style: none;
            cursor: grab;
            scroll-behavior: auto;
            overscroll-behavior-x: contain;
            -webkit-overflow-scrolling: touch;
        }
        .alumni-marquee::-webkit-scrollbar { display: none; }
        /* Muat seluruhnya: tidak ada yang bisa digulir, jadi barisnya
           dirata-tengahkan dan pudar ujungnya dimatikan agar kartu terluar
           tidak ikut meredup. */
        .alumni-marquee.is-static { justify-content: center; cursor: default; }
        .alumni-marquee-wrap:has(.is-static) {
            -webkit-mask-image: none;
            mask-image: none;
        }
        .alumni-marquee.is-dragging { cursor: grabbing; }
        .alumni-marquee.is-dragging .alumni-logo { pointer-events: none; }

        .alumni-logo {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .625rem;
            width: 13.5rem;
            margin-right: 1.25rem;
            padding: 1.5rem 1rem;
            border-radius: 1.25rem;
            border: 1px solid var(--border);
            background: var(--card, #fff);
            box-shadow: 0 2px 12px rgba(0,0,0,.05);
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
            user-select: none;
            -webkit-user-drag: none;
        }
        .alumni-logo:hover {
            transform: translateY(-4px);
            border-color: var(--primary-200);
            box-shadow: 0 14px 34px rgba(0,0,0,.10);
        }
        .alumni-logo img {
            height: 4.5rem;
            max-width: 100%;
            object-fit: contain;
            filter: grayscale(1);
            opacity: .75;
            transition: filter .25s ease, opacity .25s ease;
            -webkit-user-drag: none;
        }
        .alumni-logo:hover img { filter: grayscale(0); opacity: 1; }
        .alumni-logo-fallback {
            display: flex; align-items: center; justify-content: center;
            width: 4.5rem; height: 4.5rem;
            border-radius: 9999px;
            font-size: 1.125rem; font-weight: 800; letter-spacing: .02em;
            background: var(--primary-50);
            color: var(--primary-800);
        }
        .alumni-logo-name {
            font-size: .75rem;
            font-weight: 600;
            line-height: 1.35;
            text-align: center;
            color: var(--muted);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        @media (prefers-reduced-motion: reduce) {
            .alumni-logo, .alumni-stat { transition: none; }
        }
    </style>
</x-section-background>
@endif
