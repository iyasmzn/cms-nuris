@extends('layouts.public')

@push('head')
<style>
    /* ── Hero ─────────────────────────────────────────────── */
    .page-hero {
        position: relative;
        background: linear-gradient(135deg, var(--primary-900) 0%, var(--primary-800) 40%, var(--primary-700) 70%, var(--primary) 100%);
        padding: 9.5rem 0 3.5rem;
        overflow: hidden;
    }
    .page-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            radial-gradient(circle at 15% 50%, rgba(255,255,255,.06) 0%, transparent 50%),
            radial-gradient(circle at 85% 20%, rgba(255,255,255,.05) 0%, transparent 40%);
    }
    .page-hero-circle {
        position: absolute;
        border-radius: 9999px;
        background: rgba(255,255,255,.04);
        pointer-events: none;
    }

    /* ── Sidebar TOC ──────────────────────────────────────── */
    .sidebar-sticky   { position: sticky; top: 5.5rem; }
    .toc-item {
        display: flex;
        align-items: flex-start;
        gap: .625rem;
        padding: .35rem .5rem;
        border-radius: .375rem;
        font-size: .75rem;
        color: #6b7280;
        line-height: 1.5;
        transition: background .15s, color .15s;
        cursor: pointer;
    }
    .toc-item:hover { background: var(--primary-50); color: var(--primary-700); }
    .toc-num { flex-shrink: 0; font-weight: 700; color: var(--primary-400); font-size: .6875rem; min-width: 1.25rem; }

    /* ── Other pages list ─────────────────────────────────── */
    .page-link {
        display: flex;
        align-items: center;
        gap: .625rem;
        padding: .625rem .75rem;
        border-radius: .5rem;
        font-size: .8125rem;
        color: #6b7280;
        transition: background .15s, color .15s;
        line-height: 1.4;
    }
    .page-link:hover { background: var(--primary-50); color: var(--primary-700); }
    .page-link svg   { flex-shrink: 0; }
</style>
@include('partials.content-block-styles')
@endpush

@section('content')

{{-- ═══════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════ --}}
@php $heroCover = $page->hero_cover; @endphp
<div class="page-hero -mt-17">
    @if($heroCover->hasMedia())
        {{-- Cover pilihan admin: gambar, berkas video, atau video YouTube --}}
        <x-page-hero-media :hero="$heroCover" :title="$page->title" />
    @else
        {{-- Decorative circles --}}
        <div class="page-hero-circle" style="width:380px;height:380px;top:-120px;right:-80px;"></div>
        <div class="page-hero-circle" style="width:220px;height:220px;bottom:-60px;left:10%;"></div>
        <div class="page-hero-circle" style="width:80px;height:80px;top:30%;right:25%;background:rgba(255,255,255,.07)"></div>
    @endif

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-1.5 text-xs mb-6" style="color:color-mix(in srgb,var(--primary-200) 70%,transparent)" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-white transition-colors inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Beranda
            </a>
            <svg class="w-3 h-3 opacity-40 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="font-medium truncate" style="color:var(--primary-100)">{{ $page->title }}</span>
        </nav>

        {{-- Label --}}
        <div class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-widest mb-3" style="color:var(--primary-300)">
            <span class="w-4 h-px inline-block" style="background:var(--primary-400)"></span>
            Halaman Informasi
        </div>

        {{-- Title --}}
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white leading-tight tracking-tight mb-4">
            {{ $page->title }}
        </h1>

        {{-- Meta description --}}
        @if($page->meta_description)
            <p class="text-sm sm:text-base leading-relaxed max-w-2xl mb-6" style="color:color-mix(in srgb,var(--primary-100) 80%,transparent)">
                {{ $page->meta_description }}
            </p>
        @endif

        {{-- Date badge --}}
        <div class="inline-flex items-center gap-2 text-xs bg-white/5 border border-white/10 rounded-full px-3 py-1.5" style="color:color-mix(in srgb,var(--primary-200) 60%,transparent)">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Diperbarui {{ $page->updated_at->translatedFormat('d F Y') }}
        </div>
    </div>
</div>

{{-- Amber accent bar --}}
<div style="height:4px;background:linear-gradient(90deg,var(--primary),var(--primary-300) 50%,var(--primary-100) 100%)"></div>

{{-- ═══════════════════════════════════════════════════
     CONTENT BODY
     Isi halaman disusun dari seksi. Tanpa sidebar, tiap seksi memakai
     lebar penuh layar seperti halaman depan; dengan sidebar, seksinya
     dikecilkan jadi kartu bersudut membulat di kolom kiri.
═══════════════════════════════════════════════════ --}}
@php $hasBlocks = filled($page->blocks); @endphp

@unless($page->show_sidebar)
    @if($hasBlocks)
        @include('partials.content-blocks', ['blocks' => $page->blocks, 'title' => $page->title, 'mode' => 'full'])
    @else
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl" style="background:var(--primary-50);border:1px solid var(--primary-100)">📄</div>
            <p class="text-sm font-medium text-gray-500">Konten halaman ini belum tersedia.</p>
        </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-14">
        @include('pages.partials.page-footer-bar', ['page' => $page])
    </div>
@else
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid lg:grid-cols-4 gap-10">

        {{-- ── Main content ────────────────────────────────── --}}
        <article class="lg:col-span-3" data-aos="fade-up" data-aos-duration="500">
            @if($hasBlocks)
                @include('partials.content-blocks', ['blocks' => $page->blocks, 'title' => $page->title, 'mode' => 'boxed'])
            @else
                <div class="fi-card overflow-hidden">
                    <div style="height:3px;background:linear-gradient(90deg,var(--primary),var(--primary-300) 60%,transparent)"></div>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl" style="background:var(--primary-50);border:1px solid var(--primary-100)">📄</div>
                        <p class="text-sm font-medium text-gray-500">Konten halaman ini belum tersedia.</p>
                    </div>
                </div>
            @endif

            {{-- Back & share --}}
            <div class="mt-8">
                @include('pages.partials.page-footer-bar', ['page' => $page])
            </div>
        </article>

        {{-- ── Sidebar ──────────────────────────────────────── --}}
        <aside class="lg:col-span-1" data-aos="fade-up" data-aos-delay="100" data-aos-duration="500">
            <div class="sidebar-sticky space-y-4">

                {{-- Table of contents — dipanen dari judul seksi & heading blok teks --}}
                @php $headings = $page->content_headings; @endphp
                @if(count($headings) > 1)
                    <div class="fi-card p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-5 h-5 rounded-md flex items-center justify-center" style="background:var(--primary-50)">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--primary)">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10"/>
                                </svg>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--primary)">Daftar Isi</span>
                        </div>
                        <ol class="space-y-0.5">
                            @foreach($headings as $i => $heading)
                                <li class="toc-item">
                                    <span class="toc-num">{{ $i + 1 }}.</span>
                                    <span>{{ $heading }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                {{-- Other pages --}}
                @if($otherPages->isNotEmpty())
                    <div class="fi-card p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-5 h-5 rounded-md flex items-center justify-center" style="background:var(--primary-50)">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--primary)">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--primary)">Halaman Lainnya</span>
                        </div>
                        <ul class="space-y-0.5">
                            @foreach($otherPages as $other)
                                <li>
                                    <a href="{{ route('page.show', $other->slug) }}" class="page-link">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--primary-400)">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        {{ $other->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Back to home --}}
                <a href="{{ route('home') }}" class="btn-primary w-full justify-center text-xs group">
                    <svg class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-0.5"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Halaman Utama
                </a>
            </div>
        </aside>

    </div>
</div>
@endunless

{{-- ═══════════════════════════════════════════════════
     OTHER PAGES — bottom strip
═══════════════════════════════════════════════════ --}}
@if($otherPages->isNotEmpty())
    <section class="border-t py-12" style="border-color:#f3f4f6;background:#f9fafb">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2 mb-6">
                <span class="w-4 h-px inline-block" style="background:var(--primary)"></span>
                <span class="text-[11px] font-bold uppercase tracking-widest" style="color:var(--primary)">Informasi Lainnya</span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($otherPages as $other)
                    <a href="{{ route('page.show', $other->slug) }}"
                       class="fi-card fi-card-hover flex items-center gap-4 p-4 group"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 60 }}">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors" style="background:var(--primary-50);border:1px solid var(--primary-100)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--primary-500)">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm truncate" style="color:var(--text)">{{ $other->title }}</div>
                            <div class="text-xs mt-0.5" style="color:var(--muted)">/{{ $other->slug }}</div>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 shrink-0 transition-all group-hover:translate-x-0.5"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

@endsection
