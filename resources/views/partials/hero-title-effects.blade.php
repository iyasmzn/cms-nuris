{{--
    Warna & animasi judul hero. Dipakai bersama oleh partial `sections/hero`
    dan kotak pratinjau di Pengaturan Halaman Depan, supaya apa yang dilihat
    admin sama persis dengan yang tayang.

    Animasinya sengaja tidak menempel langsung pada judul melainkan pada
    keturunan `.hero-slide.is-active` — kelas yang dipasang-lepas Alpine —
    sehingga efeknya terputar ulang tiap kali slide itu kembali tampil.
    `.hero-title-preview` adalah pintu kedua untuk pratinjau di panel.

    Takarannya datang dari `App\Support\HeroTitleEffect::cssVars()`:
    --hero-title-color, --hero-title-accent, --hero-title-duration,
    --hero-title-delay, --hero-title-repeat.

    Sengaja tanpa @once: hero hanya sekali per halaman, sementara pratinjau di
    panel bisa dirender ulang beberapa kali dalam satu permintaan — dan penjaga
    @once akan membuat render kedua kehilangan stylesheet-nya.
--}}
<style>
    .hero-title { color: var(--hero-title-color, inherit); }

    .hero-title-text {
        position: relative;
        display: inline-block;
    }

    /* Kursor mesin ketik — berkedip sendiri, lepas dari animasi judulnya. */
    .hero-title-caret {
        display: inline-block;
        width: .07em;
        height: .95em;
        margin-left: .08em;
        vertical-align: -.08em;
        background: var(--hero-title-accent, var(--primary-400, #d97706));
        animation: hero-fx-caret 1s steps(1, end) infinite;
    }

    @keyframes hero-fx-caret {
        0%, 49%   { opacity: 1; }
        50%, 100% { opacity: 0; }
    }

    /* ── Muncul Naik ───────────────────────────────────────────── */
    :is(.hero-slide.is-active, .hero-title-preview) .hero-fx-fade-up .hero-title-text {
        animation: hero-fx-fade-up var(--hero-title-duration) var(--hero-title-delay) var(--hero-title-repeat) ease-out both;
    }

    @keyframes hero-fx-fade-up {
        0%        { opacity: 0; transform: translateY(.5em); }
        60%, 100% { opacity: 1; transform: none; }
    }

    /* ── Tersapu Muncul ────────────────────────────────────────── */
    :is(.hero-slide.is-active, .hero-title-preview) .hero-fx-wipe .hero-title-text {
        animation: hero-fx-wipe var(--hero-title-duration) var(--hero-title-delay) var(--hero-title-repeat) ease-out both;
    }

    @keyframes hero-fx-wipe {
        0%        { clip-path: inset(0 100% 0 0); }
        60%, 100% { clip-path: inset(0 -.15em 0 0); }
    }

    /* ── Garis Bawah ───────────────────────────────────────────── */
    .hero-fx-underline .hero-title-text::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: -.14em;
        height: .085em;
        border-radius: 99px;
        background: var(--hero-title-accent, var(--primary-400, #d97706));
        transform: scaleX(0);
        transform-origin: left;
    }

    :is(.hero-slide.is-active, .hero-title-preview) .hero-fx-underline .hero-title-text::after {
        animation: hero-fx-grow-x var(--hero-title-duration) var(--hero-title-delay) var(--hero-title-repeat) ease-out both;
    }

    @keyframes hero-fx-grow-x {
        0%        { transform: scaleX(0); }
        60%, 100% { transform: scaleX(1); }
    }

    /* ── Stabilo ───────────────────────────────────────────────────
       `isolation` menahan sapuan ber-z-index negatif tetap di dalam
       judulnya sendiri, jadi ia duduk di belakang huruf tanpa ikut
       jatuh ke belakang latar slide. */
    .hero-fx-highlight .hero-title-text {
        isolation: isolate;
        padding: 0 .14em;
    }

    .hero-fx-highlight .hero-title-text::before {
        content: '';
        position: absolute;
        inset: 52% 0 .02em 0;
        z-index: -1;
        border-radius: .08em;
        background: var(--hero-title-accent, var(--primary-400, #d97706));
        opacity: .85;
        transform: scaleX(0);
        transform-origin: left;
    }

    :is(.hero-slide.is-active, .hero-title-preview) .hero-fx-highlight .hero-title-text::before {
        animation: hero-fx-grow-x var(--hero-title-duration) var(--hero-title-delay) var(--hero-title-repeat) ease-out both;
    }

    /* ── Bingkai ───────────────────────────────────────────────────
       Dua pseudo-element: garis mendatar melebar lebih dulu, garis
       tegak menyusul di paruh kedua, sehingga bingkainya terasa
       digambar dan tetap sinkron saat diulang. */
    .hero-fx-border .hero-title-text {
        padding: .18em .32em;
    }

    .hero-fx-border .hero-title-text::before,
    .hero-fx-border .hero-title-text::after {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
    }

    .hero-fx-border .hero-title-text::before {
        border-top: .045em solid var(--hero-title-accent, var(--primary-400, #d97706));
        border-bottom: .045em solid var(--hero-title-accent, var(--primary-400, #d97706));
        transform: scaleX(0);
        transform-origin: left;
    }

    .hero-fx-border .hero-title-text::after {
        border-left: .045em solid var(--hero-title-accent, var(--primary-400, #d97706));
        border-right: .045em solid var(--hero-title-accent, var(--primary-400, #d97706));
        transform: scaleY(0);
        transform-origin: top;
    }

    :is(.hero-slide.is-active, .hero-title-preview) .hero-fx-border .hero-title-text::before {
        animation: hero-fx-border-h var(--hero-title-duration) var(--hero-title-delay) var(--hero-title-repeat) ease-out both;
    }

    :is(.hero-slide.is-active, .hero-title-preview) .hero-fx-border .hero-title-text::after {
        animation: hero-fx-border-v var(--hero-title-duration) var(--hero-title-delay) var(--hero-title-repeat) ease-out both;
    }

    @keyframes hero-fx-border-h {
        0%        { transform: scaleX(0); }
        35%, 100% { transform: scaleX(1); }
    }

    @keyframes hero-fx-border-v {
        0%, 35%   { transform: scaleY(0); }
        70%, 100% { transform: scaleY(1); }
    }

    /* ── Gradasi Berjalan ──────────────────────────────────────── */
    .hero-fx-gradient .hero-title-text,
    .hero-fx-shine .hero-title-text {
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .hero-fx-gradient .hero-title-text {
        background-image: linear-gradient(100deg,
            var(--hero-title-color, #ffffff) 0%,
            var(--hero-title-accent, var(--primary-400, #d97706)) 30%,
            var(--hero-title-color, #ffffff) 60%,
            var(--hero-title-accent, var(--primary-400, #d97706)) 100%);
        background-size: 300% 100%;
    }

    :is(.hero-slide.is-active, .hero-title-preview) .hero-fx-gradient .hero-title-text {
        animation: hero-fx-gradient var(--hero-title-duration) var(--hero-title-delay) var(--hero-title-repeat) linear both;
    }

    @keyframes hero-fx-gradient {
        0%   { background-position: 0% 50%; }
        100% { background-position: -200% 50%; }
    }

    /* ── Kilau Melintas ────────────────────────────────────────── */
    .hero-fx-shine .hero-title-text {
        background-image: linear-gradient(100deg,
            var(--hero-title-color, #ffffff) 0 42%,
            var(--hero-title-accent, var(--primary-400, #d97706)) 50%,
            var(--hero-title-color, #ffffff) 58% 100%);
        background-size: 260% 100%;
    }

    :is(.hero-slide.is-active, .hero-title-preview) .hero-fx-shine .hero-title-text {
        animation: hero-fx-shine var(--hero-title-duration) var(--hero-title-delay) var(--hero-title-repeat) ease-in-out both;
    }

    @keyframes hero-fx-shine {
        0%        { background-position: 160% 50%; }
        70%, 100% { background-position: -60% 50%; }
    }

    /* ── Cahaya Berdenyut ──────────────────────────────────────── */
    :is(.hero-slide.is-active, .hero-title-preview) .hero-fx-glow .hero-title-text {
        animation: hero-fx-glow var(--hero-title-duration) var(--hero-title-delay) var(--hero-title-repeat) ease-in-out both;
    }

    @keyframes hero-fx-glow {
        0%, 100% { text-shadow: 0 0 0 transparent; }
        50%      { text-shadow: 0 0 .4em var(--hero-title-accent, var(--primary-400, #d97706)); }
    }

    /* Judul harus tetap terbaca walau geraknya dimatikan: animasi
       dilucuti, tapi warna & sapuan tetap pada posisi akhirnya. */
    @media (prefers-reduced-motion: reduce) {
        .hero-title-text,
        .hero-title-text::before,
        .hero-title-text::after,
        .hero-title-caret {
            animation: none !important;
            transform: none !important;
            clip-path: none !important;
            opacity: 1;
        }
    }
</style>
