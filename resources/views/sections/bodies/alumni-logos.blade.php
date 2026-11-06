{{--
    Baris logo kampus tujuan alumni — bagian bawah seksi Jejak Alumni, dipakai
    halaman depan (sections/alumni) dan blok data di halaman & program.

    Params:
      $alumniUniversities — koleksi AlumniUniversity
      $alumniMarquee      — App\Support\AlumniMarquee
--}}
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

        <div class="alumni-marquee-wrap" style="{{ $alumniMarquee->styleAttribute() }}">
            <div @class(['alumni-marquee', 'is-color' => ! $alumniMarquee->grayscale])
                 role="list"
                 aria-label="Logo kampus tujuan alumni"
                 x-data="{
                     /* Piksel per detik, bertanda: negatif berarti ke kanan. */
                     speed: {{ $alumniMarquee->signedSpeed() }},
                     autoplay: {{ $alumniMarquee->autoplay ? 'true' : 'false' }},
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

                         if (! this.autoplay) return
                         if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return

                         /* Perpindahan dihitung dari selisih waktu antar frame supaya
                            kecepatannya sama di layar 60 Hz maupun 120 Hz. Lompatan
                            besar (tab kembali aktif) dibatasi agar baris tidak melesat. */
                         let previous = 0
                         const step = (now) => {
                             const elapsed = previous ? Math.min((now - previous) / 1000, 0.1) : 0
                             previous = now

                             if (this.rolling && ! this.paused && ! this.dragging) {
                                 this.move(this.offset + this.speed * elapsed)
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
                         if (! this.rolling || this.dragging) return
                         if (this.autoplay && ! this.paused) return
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
                 @if($alumniMarquee->autoplay && $alumniMarquee->pauseOnHover)
                 @mouseenter="hold()"
                 @mouseleave="release(0)"
                 @endif
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
                        @if($alumniMarquee->showName)
                            <span class="alumni-logo-name">{{ $university->name }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
