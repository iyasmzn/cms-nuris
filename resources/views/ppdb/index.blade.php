@extends('layouts.public')

{{--
    Halaman depan PPDB yang disusun admin di panel (PPDB / SPMB → Halaman
    Depan PPDB): hero di atas, lalu seksi-seksi berurutan. Seksi data PPDB
    dirender partial di ppdb/landing/, blok lain oleh content-block-body.
--}}

@push('head')
<style>
    .ppdb-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0a1628 100%);
        position: relative;
        overflow: hidden;
    }
    .ppdb-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background:
            radial-gradient(ellipse 70% 70% at 10% 50%, rgba(217,119,6,.25) 0%, transparent 55%),
            radial-gradient(ellipse 50% 50% at 90% 10%, rgba(251,191,36,.12) 0%, transparent 50%);
    }
    .ppdb-hero-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
        padding: .85rem 1.75rem; border-radius: .875rem;
        font-size: .9375rem; font-weight: 700; transition: all .18s;
        width: 100%;
    }
    @media (min-width: 640px) { .ppdb-hero-btn { width: auto; } }
    .ppdb-hero-btn-primary { background: #f59e0b; color: #fff; box-shadow: 0 8px 24px -8px rgba(245,158,11,.6); }
    .ppdb-hero-btn-primary:hover { background: #fbbf24; transform: translateY(-1px); }
    .ppdb-hero-btn-ghost { background: rgba(255,255,255,.08); color: #fff; border: 1.5px solid rgba(255,255,255,.3); backdrop-filter: blur(6px); }
    .ppdb-hero-btn-ghost:hover { background: rgba(255,255,255,.16); border-color: rgba(255,255,255,.5); }

    /* ── Seksi data PPDB ─────────────────────────────────── */
    .block-section { scroll-margin-top: 5rem; }
    .pl-grid { display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 17rem), 1fr)); }
    .pl-grid-wide { grid-template-columns: repeat(auto-fill, minmax(min(100%, 21rem), 1fr)); }
    .pl-narrow { max-width: 56rem; }

    .pl-card-hover { transition: transform .18s, border-color .18s, box-shadow .18s; }
    .pl-card-hover:hover { transform: translateY(-4px); border-color: #d97706; }

    .pl-icon-tile {
        width: 3.5rem; height: 3.5rem; border-radius: 1rem; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: #fffbeb; border: 1px solid #fde68a;
    }
    .pl-icon-tile-sm { width: 2.75rem; height: 2.75rem; border-radius: .75rem; }

    .pl-quota-number { font-size: 2.5rem; font-weight: 800; line-height: 1; letter-spacing: -.03em; color: var(--text); }
    .pl-quota-number small { font-size: .875rem; font-weight: 600; letter-spacing: 0; color: var(--muted); margin-left: .25rem; }
    .pl-progress { height: .5rem; border-radius: 9999px; background: color-mix(in srgb, var(--muted) 18%, transparent); overflow: hidden; }
    .pl-progress > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #f59e0b, #d97706); }
    .pl-progress-full > span { background: linear-gradient(90deg, #f87171, #dc2626); }

    .pl-step { position: relative; padding-top: 2rem; }
    .pl-step-num {
        position: absolute; top: -.9rem; left: 1.5rem;
        min-width: 2.25rem; height: 2.25rem; padding: 0 .5rem; border-radius: 9999px;
        display: flex; align-items: center; justify-content: center;
        background: #d97706; color: #fff; font-size: .8125rem; font-weight: 800;
        box-shadow: 0 4px 10px rgba(217,119,6,.35);
    }

    /* Kolom tetap (Kartu Sebaris): ponsel 1, layar sedang maks. 2 */
    .pl-cols { display: grid; gap: 1.5rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 640px) { .pl-cols-2, .pl-cols-3, .pl-cols-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) {
        .pl-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .pl-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    /* Jalur — kartu bergambar */
    .pl-path-media { aspect-ratio: 4 / 3; overflow: hidden; background: #fffbeb; display: flex; align-items: center; justify-content: center; }
    .pl-path-media img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
    .pl-path-media .pl-path-media-icon { width: 6rem; height: 6rem; object-fit: contain; }
    .pl-path-media-emoji { font-size: 5rem; line-height: 1; }
    .pl-card-hover:hover .pl-path-media img { transform: scale(1.04); }

    /* Jalur — kartu ikon & daftar ringkas */
    .pl-path-icon {
        display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;
        background: #fffbeb; border: 1px solid #fde68a;
    }
    .pl-path-icon-lg { width: 5rem; height: 5rem; border-radius: 1.25rem; margin: 1.5rem 1.5rem 0; }
    .pl-path-icon-lg .pl-path-icon-emoji { font-size: 2.75rem; line-height: 1; }
    .pl-path-icon-lg .pl-path-icon-img { width: 3.25rem; height: 3.25rem; object-fit: contain; }
    .pl-path-icon-sm { width: 3.75rem; height: 3.75rem; border-radius: 1rem; }
    .pl-path-icon-sm .pl-path-icon-emoji { font-size: 2rem; line-height: 1; }
    .pl-path-icon-sm .pl-path-icon-img { width: 2.5rem; height: 2.5rem; object-fit: contain; }
    .pl-path-icon-cover { width: 100%; height: 100%; object-fit: cover; }

    .pl-path-body { display: flex; flex-direction: column; flex: 1; min-width: 0; padding: 1.5rem; }
    .pl-paths-icon .pl-path-body { padding-top: 1.25rem; }
    .pl-path-name { font-size: 1.125rem; font-weight: 700; line-height: 1.4; margin-bottom: .5rem; }
    .pl-path-desc { font-size: .875rem; line-height: 1.65; margin-bottom: 1rem; }
    .pl-path-badges { display: flex; flex-wrap: wrap; gap: .375rem; margin-bottom: 1rem; }
    .pl-path-badges:last-child { margin-bottom: 0; }

    .pl-paths-list { gap: 1rem; }
    .pl-path-row { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; }
    .pl-path-row .pl-path-body { padding: 0; }
    .pl-path-row .pl-path-name { font-size: 1rem; margin-bottom: .25rem; }
    .pl-path-row .pl-path-desc {
        margin-bottom: .5rem;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .pl-path-row-arrow { flex-shrink: 0; }
    .pl-path-row.pl-card-hover:hover { transform: translateY(-2px); }
    .pl-card-link:focus-visible { outline: 2px solid #d97706; outline-offset: 3px; }
    .pl-card-cta svg { transition: transform .18s; }
    .pl-card-link:hover .pl-card-cta { color: #b45309; }
    .pl-card-link:hover .pl-card-cta svg { transform: translateX(3px); }

    .pl-fee-row:nth-child(even) { background: rgba(0,0,0,.025); }

    /* ── Timeline ─────────────────────────────────────────
       Dasarnya vertikal (juga dipakai semua tampilan di ponsel): garis di
       kiri, penanda bulat, kartu di kanan. Zig-zag & horizontal baru
       berlaku mulai layar sedang. */
    .pl-tl { position: relative; list-style: none; margin: 0; padding: 0; }
    .pl-tl::before {
        content: ''; position: absolute; top: 1.375rem; bottom: 1.375rem; left: 1.375rem;
        width: 2px; transform: translateX(-50%);
        background: linear-gradient(#fde68a, #f59e0b 50%, #fde68a);
    }
    .pl-tl-vertical { max-width: 48rem; }
    .pl-tl-alternate { max-width: 64rem; }
    .pl-tl-item { position: relative; display: grid; grid-template-columns: 2.75rem minmax(0, 1fr); column-gap: 1rem; align-items: start; }
    .pl-tl-item + .pl-tl-item { margin-top: 1.25rem; }
    .pl-tl-marker {
        position: relative; z-index: 1;
        width: 2.75rem; height: 2.75rem; border-radius: 9999px;
        display: flex; align-items: center; justify-content: center;
        background: #fffbeb; border: 2px solid #f59e0b; color: #b45309;
        font-size: .875rem; font-weight: 800;
    }
    .pl-tl-marker-icon { font-size: 1.25rem; line-height: 1; }
    .pl-tl-item-active .pl-tl-marker { background: #f59e0b; color: #fff; box-shadow: 0 0 0 6px rgba(245,158,11,.2); }
    .pl-tl-card { padding: 1.125rem 1.375rem; }
    .pl-tl-item-active .pl-tl-card { border-color: #f59e0b; box-shadow: 0 10px 28px -14px rgba(245,158,11,.55); }
    .pl-tl-label {
        display: inline-block; margin-bottom: .5rem; padding: .125rem .625rem; border-radius: 9999px;
        font-size: .75rem; font-weight: 700; color: #b45309; background: #fffbeb; border: 1px solid #fde68a;
    }
    .pl-tl-title { font-size: 1rem; font-weight: 700; line-height: 1.4; }
    .pl-tl-desc { margin-top: .375rem; font-size: .875rem; line-height: 1.65; }

    @media (min-width: 768px) {
        .pl-tl-alternate::before { left: 50%; }
        .pl-tl-alternate .pl-tl-item { grid-template-columns: minmax(0, 1fr) 2.75rem minmax(0, 1fr); column-gap: 1.5rem; }
        .pl-tl-alternate .pl-tl-marker { grid-column: 2; grid-row: 1; }
        .pl-tl-alternate .pl-tl-card { grid-column: 3; grid-row: 1; }
        .pl-tl-alternate .pl-tl-item:nth-child(odd) .pl-tl-card { grid-column: 1; text-align: right; }

        /* Garis horizontal digambar per titik agar ikut tergeser saat berjajar panjang */
        .pl-tl-horizontal {
            display: grid; grid-auto-flow: column; grid-auto-columns: minmax(14rem, 1fr); column-gap: 1.25rem;
            overflow-x: auto; padding-bottom: .75rem; scroll-snap-type: x proximity;
        }
        .pl-tl-horizontal::before { display: none; }
        .pl-tl-horizontal .pl-tl-item { grid-template-columns: minmax(0, 1fr); row-gap: 1rem; scroll-snap-align: start; }
        .pl-tl-horizontal .pl-tl-item + .pl-tl-item { margin-top: 0; }
        .pl-tl-horizontal .pl-tl-item:not(:last-child)::after {
            content: ''; position: absolute; top: 1.375rem; left: 2.75rem; right: -1.25rem;
            height: 2px; transform: translateY(-50%); background: #fcd34d;
        }
        .pl-tl-horizontal .pl-tl-card { height: 100%; }
    }

    .pl-collapse { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .3s ease; }
    .pl-collapse-open { grid-template-rows: 1fr; }
    .pl-answer > *:first-child { margin-top: 0; }
    .pl-answer > *:last-child  { margin-bottom: 0; }
    .pl-answer p  { margin: .75rem 0; }
    .pl-answer ul, .pl-answer ol { margin: .75rem 0; padding-left: 1.35rem; }
    .pl-answer ul { list-style: disc; }
    .pl-answer ol { list-style: decimal; }
    .pl-answer a  { color: #d97706; font-weight: 600; text-decoration: underline; }
</style>
@include('partials.content-block-styles')
@endpush

@section('content')
@php
    $fill = fn (?string $text): string => trim(\App\Support\PpdbLanding::fill($text));
    $heroCover = \App\Support\PageHero::fromArray($hero);
    $heroTitle = $fill($hero['title'] ?? '');
    $heroBadge = $fill($hero['badge'] ?? '');
    $heroHighlight = $fill($hero['highlight'] ?? '');
    $heroSubtitle = $fill($hero['subtitle'] ?? '');
    $heroButtons = collect(['primary', 'secondary'])
        ->map(fn (string $key): array => [
            'label' => $fill($hero["{$key}_label"] ?? ''),
            'url' => trim((string) ($hero["{$key}_url"] ?? '')),
            'primary' => $key === 'primary',
        ])
        ->filter(fn (array $button): bool => $button['label'] !== '' && $button['url'] !== '');
@endphp

{{-- ═══════════════════════ HERO ═══════════════════════════════ --}}
<section class="ppdb-hero -mt-17 pt-32 pb-16 sm:pt-40 sm:pb-24">
    @if($heroCover->hasMedia())
        <x-page-hero-media :hero="$heroCover" :title="$heroTitle" />
    @else
        <x-hero-geo />
    @endif

    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 z-10 text-center" data-aos="fade-up">
        @if($heroBadge !== '')
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 border border-amber-500/30 mb-5">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                <span class="text-xs font-bold text-amber-300 uppercase tracking-widest">{{ $heroBadge }}</span>
            </div>
        @endif

        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight mb-4">
            {{ $heroTitle }}
            @if($heroHighlight !== '')
                <br><span class="text-amber-400">{{ $heroHighlight }}</span>
            @endif
        </h1>

        @if($heroSubtitle !== '')
            <p class="text-white/75 text-sm sm:text-base leading-relaxed max-w-2xl mx-auto">{{ $heroSubtitle }}</p>
        @endif

        @if($heroButtons->isNotEmpty())
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                @foreach($heroButtons as $button)
                    @php $isExternal = \Illuminate\Support\Str::startsWith($button['url'], ['http://', 'https://']); @endphp
                    <a href="{{ $button['url'] }}" @if($isExternal) target="_blank" rel="noopener" @endif
                       class="ppdb-hero-btn {{ $button['primary'] ? 'ppdb-hero-btn-primary' : 'ppdb-hero-btn-ghost' }}">
                        {{ $button['label'] }}
                        @if($button['primary'])
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ═══════════════════════ SEKSI ══════════════════════════════ --}}
@include('partials.content-blocks', [
    'blocks' => $sections,
    'title' => $heroTitle,
    'mode' => 'full',
    'customBodies' => \App\Support\PpdbLanding::views(),
])
@endsection
