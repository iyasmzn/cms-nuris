{{-- Stylesheet seksi Jejak Alumni: kartu capaian & baris logo kampus. --}}
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
        /* Lebar kartu, jarak antar-kartu, dan tinggi logo diatur admin lewat
           Pengaturan Halaman Depan; nilai cadangannya = rancangan aslinya. */
        width: var(--alumni-logo-card-width, 13.5rem);
        margin-right: var(--alumni-logo-gap, 1.25rem);
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
        height: var(--alumni-logo-height, 4.5rem);
        max-width: 100%;
        object-fit: contain;
        filter: grayscale(1);
        opacity: .75;
        transition: filter .25s ease, opacity .25s ease;
        -webkit-user-drag: none;
    }
    .alumni-logo:hover img { filter: grayscale(0); opacity: 1; }
    /* Logo berwarna sejak awal, tanpa efek abu-abu. */
    .alumni-marquee.is-color .alumni-logo img { filter: none; opacity: 1; }
    .alumni-logo-fallback {
        display: flex; align-items: center; justify-content: center;
        width: var(--alumni-logo-height, 4.5rem); height: var(--alumni-logo-height, 4.5rem);
        border-radius: 9999px;
        /* Inisial ikut mengecil bersama ukuran logo agar tetap muat di lingkarannya. */
        font-size: clamp(.75rem, calc(var(--alumni-logo-height, 4.5rem) * .25), 1.75rem);
        font-weight: 800; letter-spacing: .02em;
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
