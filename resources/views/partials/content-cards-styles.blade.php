{{--
    Gaya & perilaku kartu yang dipakai bersama oleh seksi dinamis halaman depan
    dan blok kartu "Konten Tambahan" (deretan kartu maupun carousel). Dijaga
    @once agar tetap sekali cetak walau di-include dari beberapa tempat.
--}}
@once
<style>
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
        font-size: calc(1.0625rem * var(--cs-scale, 1));
        font-weight: 700;
        line-height: 1.4;
        color: var(--text);
    }
    .cs-card-text {
        font-size: calc(.9375rem * var(--cs-scale, 1));
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
