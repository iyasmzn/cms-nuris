{{-- Shared styles for the "Konten Tambahan" blocks (teks / cover / carousel / gallery / lightbox / gambar-teks / kartu). --}}
<style>
    /* ── Seksi ────────────────────────────────────────────
       Tiap blok berdiri sebagai seksi sendiri saat dipakai sebagai
       konten utama. Warnanya memakai token tema agar seksi berlatar
       gambar gelap (.section-light) ikut berubah terang. */
    .block-section-inner { max-width: 80rem; margin: 0 auto; padding-left: 1rem; padding-right: 1rem; }
    @media (min-width: 640px) { .block-section-inner { padding-left: 1.5rem; padding-right: 1.5rem; } }
    @media (min-width: 1024px) { .block-section-inner { padding-left: 2rem; padding-right: 2rem; } }

    .block-pad-none { padding-top: 0; padding-bottom: 0; }
    .block-pad-sm   { padding-top: 2.5rem; padding-bottom: 2.5rem; }
    .block-pad-md   { padding-top: 4rem;   padding-bottom: 4rem; }
    .block-pad-lg   { padding-top: 6rem;   padding-bottom: 6rem; }
    @media (min-width: 640px) {
        .block-pad-sm { padding-top: 3rem; padding-bottom: 3rem; }
        .block-pad-md { padding-top: 5rem; padding-bottom: 5rem; }
        .block-pad-lg { padding-top: 7rem; padding-bottom: 7rem; }
    }

    /* Seksi berkartu: dikecilkan ke lebar kolom artikel dan dibulatkan sudutnya */
    .block-section-boxed {
        border-radius: 1.5rem;
        overflow: hidden;
        border: 1px solid var(--border);
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        margin-bottom: 1.5rem;
    }
    .block-section-boxed .block-section-inner {
        max-width: none;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }
    @media (min-width: 640px) {
        .block-section-boxed .block-section-inner { padding-left: 2.5rem; padding-right: 2.5rem; }
    }
    .block-section-boxed.block-pad-md { padding-top: 2.5rem; padding-bottom: 2.5rem; }
    .block-section-boxed.block-pad-lg { padding-top: 3.5rem; padding-bottom: 3.5rem; }
    .block-section-boxed.block-pad-sm { padding-top: 1.75rem; padding-bottom: 1.75rem; }

    /* Sudut kartu memotong apa pun yang keluar batas, jadi panah carousel
       ditarik masuk agar tidak ikut terpotong */
    .block-section-boxed .cs-arrow-prev { left: 0; }
    .block-section-boxed .cs-arrow-next { right: 0; }

    .block-section-head { margin-bottom: 1.75rem; }
    .block-section-eyebrow { display: block; margin-bottom: .5rem; }
    .block-section-title {
        font-size: 1.75rem; font-weight: 800; letter-spacing: -.02em;
        line-height: 1.2; color: var(--text);
    }
    @media (min-width: 640px) { .block-section-title { font-size: 2.25rem; } }
    .block-section-boxed .block-section-title { font-size: 1.5rem; }
    @media (min-width: 640px) { .block-section-boxed .block-section-title { font-size: 1.75rem; } }

    /* Blok pertama & terakhir dalam satu seksi tidak perlu jarak luar sendiri */
    .block-section-inner > :first-child { margin-top: 0; }
    .block-section-inner > :last-child  { margin-bottom: 0; }

    /* ── Teks bebas ───────────────────────────────────────── */
    .block-prose { line-height: 1.85; color: var(--muted); font-size: 1.0625rem; margin: 1.5rem 0; }
    @media (min-width: 640px) { .block-prose { font-size: 1.125rem; } }
    .block-prose > *:first-child { margin-top: 0; }
    .block-prose > *:last-child  { margin-bottom: 0; }
    .block-prose h2 { font-size: 1.5rem; font-weight: 800; color: var(--text); margin: 2rem 0 .875rem; line-height: 1.3; }
    .block-prose h3 { font-size: 1.25rem; font-weight: 700; color: var(--text); margin: 1.75rem 0 .75rem; line-height: 1.35; }
    .block-prose h4 { font-size: 1.0625rem; font-weight: 700; color: var(--text); margin: 1.5rem 0 .625rem; line-height: 1.4; }
    .block-prose p  { margin-bottom: 1.375rem; }
    .block-prose ul, .block-prose ol { margin: 0 0 1.375rem; padding-left: 1.5rem; }
    .block-prose ul { list-style: disc; }
    .block-prose ol { list-style: decimal; }
    .block-prose li { margin-bottom: .5rem; line-height: 1.75; }
    .block-prose blockquote {
        border-left: 3px solid var(--primary); background: var(--primary-50);
        padding: .875rem 1.25rem; margin: 1.5rem 0; border-radius: 0 .5rem .5rem 0;
        color: var(--text); font-style: italic;
    }
    .section-light .block-prose blockquote { background: rgba(255,255,255,.08); }
    .block-prose a { color: var(--primary); text-decoration: underline; text-underline-offset: 3px; font-weight: 500; }
    .block-prose a:hover { color: var(--primary-700); }
    .block-prose strong { color: var(--text); font-weight: 700; }
    .block-prose img { border-radius: .75rem; margin: 1.75rem 0; max-width: 100%; }
    .block-prose code { background: var(--primary-50); padding: .125rem .4rem; border-radius: .25rem; font-size: .875em; color: var(--primary); }
    .block-prose table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: .875rem; }
    .block-prose th { background: var(--primary-50); color: var(--primary-800); font-weight: 700; padding: .625rem 1rem; text-align: left; border-bottom: 2px solid var(--primary-200); }
    .block-prose td { padding: .625rem 1rem; border-bottom: 1px solid var(--border); vertical-align: top; }
    .block-prose tr:last-child td { border-bottom: none; }

    /* ── Gambar & Teks ────────────────────────────────────── */
    .block-media { margin: 2.5rem 0; }
    .block-media-split { display: grid; gap: 1.75rem; }
    @media (min-width: 1024px) {
        .block-media-split { grid-template-columns: 1fr 1fr; gap: 2.5rem; align-items: center; }
    }
    .block-media-title {
        font-size: 1.375rem; font-weight: 800; color: var(--text);
        line-height: 1.3; margin-bottom: .875rem;
    }
    .block-media .block-prose { margin: 0; font-size: 1rem; }
    @media (min-width: 640px) { .block-media .block-prose { font-size: 1.0625rem; } }

    /* ── Kartu ────────────────────────────────────────────── */
    .block-cards { margin: 2.5rem 0; }
    /* ── Blocks ───────────────────────────────────────────── */
    .block-label {
        display: inline-flex; align-items: center; gap: .4rem;
        font-size: .6875rem; font-weight: 700; letter-spacing: .07em;
        text-transform: uppercase; color: var(--primary); margin-bottom: .75rem;
    }
    .block-label::before {
        content: ''; display: inline-block; width: 1rem; height: 2px;
        background: var(--primary); border-radius: 1px;
    }

    /* Cover Image */
    .block-cover { margin: 2rem 0; }
    .block-cover img {
        width: 100%; border-radius: .875rem; object-fit: cover;
        max-height: 520px; display: block;
        box-shadow: 0 8px 32px rgba(0,0,0,.12);
    }
    .block-cover figcaption {
        text-align: center; font-size: .8rem; color: var(--muted);
        margin-top: .625rem; font-style: italic;
    }

    /* Carousel */
    .block-carousel { margin: 2rem 0; border-radius: .875rem; overflow: hidden; position: relative; }
    .block-carousel img { width: 100%; max-height: 520px; object-fit: cover; display: block; }
    .carousel-btn {
        position: absolute; top: 50%; transform: translateY(-50%);
        width: 2.5rem; height: 2.5rem; border-radius: 9999px;
        background: rgba(255,255,255,.9); backdrop-filter: blur(4px);
        border: 1px solid rgba(255,255,255,.6);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: background .15s, transform .15s;
        box-shadow: 0 2px 8px rgba(0,0,0,.15);
    }
    .carousel-btn:hover { background: #fff; transform: translateY(-50%) scale(1.08); }
    .carousel-btn svg   { width: 1rem; height: 1rem; color: #374151; flex-shrink: 0; }

    /* Gallery */
    .block-gallery { margin: 2rem 0; }
    .gallery-item {
        position: relative; overflow: hidden; border-radius: .75rem;
        cursor: zoom-in; aspect-ratio: 4/3;
    }
    .gallery-item img {
        width: 100%; height: 100%; object-fit: cover;
        transition: transform .5s ease;
    }
    .gallery-item:hover img { transform: scale(1.06); }
    .gallery-caption {
        position: absolute; inset-x: 0; bottom: 0;
        background: linear-gradient(to top, rgba(0,0,0,.65), transparent);
        padding: .75rem .875rem .625rem;
        transform: translateY(100%); transition: transform .3s ease;
    }
    .gallery-item:hover .gallery-caption { transform: translateY(0); }
    .gallery-caption p { color: #fff; font-size: .75rem; line-height: 1.4; }

    /* CTA Button */
    .block-cta { margin: 2rem 0; }
    .block-cta-btn {
        display: inline-flex; align-items: center; justify-content: center;
        padding: .8rem 1.75rem; border-radius: 9999px;
        font-size: .9375rem; font-weight: 600; line-height: 1;
        text-decoration: none; cursor: pointer;
        transition: transform .15s ease, box-shadow .15s ease, background .15s ease, color .15s ease;
    }
    .block-cta-primary {
        background: var(--cta, var(--primary)); color: #fff;
        box-shadow: 0 4px 16px rgba(0,0,0,.15);
    }
    .block-cta-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.22); }
    .block-cta-outline {
        background: transparent; color: var(--cta, var(--primary));
        border: 2px solid var(--cta, var(--primary));
    }
    .block-cta-outline:hover { background: var(--cta, var(--primary)); color: #fff; transform: translateY(-2px); }

    /* Lightbox */
    .lightbox-overlay {
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,.92); backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        padding: 1.5rem;
    }
    .lightbox-img {
        max-width: 100%; max-height: 90vh;
        border-radius: .75rem; object-fit: contain;
        box-shadow: 0 24px 80px rgba(0,0,0,.6);
    }
</style>
