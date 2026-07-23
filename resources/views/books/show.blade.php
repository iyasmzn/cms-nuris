@extends('layouts.public')

@section('content')

{{-- ── Breadcrumb ──────────────────────────────────────────── --}}
<div class="-mt-17" style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#0c1a14 100%);border-bottom:1px solid rgba(255,255,255,.08)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-5 sm:pt-28 sm:pb-6">
        <nav class="flex items-center gap-2 text-sm" style="color:rgba(255,255,255,.6)">
            <a href="{{ route('home') }}" class="hover:opacity-75 transition-opacity">Beranda</a>
            <span>/</span>
            <a href="{{ route('books.index') }}" class="hover:opacity-75 transition-opacity">Buku</a>
            <span>/</span>
            <span class="font-medium line-clamp-1" style="color:#fff">{{ $book->title }}</span>
        </nav>
    </div>
</div>

{{-- ── Detail ──────────────────────────────────────────────── --}}
<section class="py-16 sm:py-20" style="background:var(--bg)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-5 gap-10 lg:gap-16">

            {{-- Cover --}}
            <div class="lg:col-span-2" data-aos="fade-right">
                <div class="sticky top-28">
                    <div class="fi-card overflow-hidden rounded-2xl shadow-xl"
                         style="aspect-ratio:3/4;max-width:360px;margin:0 auto">
                        <img src="{{ $book->cover_url }}"
                             alt="{{ $book->title }}"
                             class="w-full h-full object-cover">
                    </div>
                </div>
            </div>

            {{-- Info --}}
            <div class="lg:col-span-3" data-aos="fade-left">
                @if($book->category)
                <span class="inline-block text-xs font-bold px-3 py-1 rounded-full mb-4"
                      style="background:rgba(8,72,74,.1);color:var(--primary)">
                    {{ $book->category }}
                </span>
                @endif

                <h1 class="text-3xl sm:text-4xl font-extrabold leading-tight mb-3" style="color:var(--text)">
                    {{ $book->title }}
                </h1>

                @if($book->author)
                <p class="text-base mb-6" style="color:var(--muted)">oleh <strong style="color:var(--text)">{{ $book->author }}</strong></p>
                @endif

                {{-- Meta --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-8 p-5 rounded-xl" style="background:var(--bg-alt)">
                    @if($book->publisher)
                    <div>
                        <div class="text-xs font-semibold mb-0.5" style="color:var(--muted)">Penerbit</div>
                        <div class="text-sm font-medium" style="color:var(--text)">{{ $book->publisher }}</div>
                    </div>
                    @endif
                    @if($book->published_year)
                    <div>
                        <div class="text-xs font-semibold mb-0.5" style="color:var(--muted)">Tahun</div>
                        <div class="text-sm font-medium" style="color:var(--text)">{{ $book->published_year }}</div>
                    </div>
                    @endif
                    @if($book->pages)
                    <div>
                        <div class="text-xs font-semibold mb-0.5" style="color:var(--muted)">Halaman</div>
                        <div class="text-sm font-medium" style="color:var(--text)">{{ $book->pages }} hal</div>
                    </div>
                    @endif
                    @if($book->isbn)
                    <div>
                        <div class="text-xs font-semibold mb-0.5" style="color:var(--muted)">ISBN</div>
                        <div class="text-sm font-medium" style="color:var(--text)">{{ $book->isbn }}</div>
                    </div>
                    @endif
                    @if($book->weight_gram)
                    <div>
                        <div class="text-xs font-semibold mb-0.5" style="color:var(--muted)">Berat</div>
                        <div class="text-sm font-medium" style="color:var(--text)">{{ $book->weight_gram }}g</div>
                    </div>
                    @endif
                </div>

                {{-- Price & Stock --}}
                <div class="flex items-end gap-4 mb-6">
                    <div class="text-4xl font-extrabold" style="color:var(--primary)">
                        {{ $book->formatted_price }}
                    </div>
                    <div class="text-sm pb-1" style="color:var(--muted)">
                        Stok: <strong style="color:{{ $book->stock > 0 ? 'var(--primary)' : '#ef4444' }}">
                            {{ $book->stock > 0 ? $book->stock.' tersedia' : 'Habis' }}
                        </strong>
                    </div>
                </div>

                {{-- CTA --}}
                <div class="flex flex-wrap gap-3 mb-10">
                    @if($book->is_available && $book->stock > 0)
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', setting('contact_whatsapp', '')) }}?text={{ urlencode('Assalamu\'alaikum, saya ingin memesan buku: '.$book->title) }}"
                       target="_blank" rel="noopener"
                       class="btn-primary flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Pesan via WhatsApp
                    </a>
                    @else
                    <span class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold"
                          style="background:var(--border);color:var(--muted)">
                        Stok Habis
                    </span>
                    @endif
                    <a href="{{ route('books.index') }}" class="btn-outline">← Kembali</a>
                </div>

                {{-- Description --}}
                @if($book->description)
                <div>
                    <h2 class="text-lg font-bold mb-3" style="color:var(--text)">Deskripsi Buku</h2>
                    <p class="text-base leading-relaxed" style="color:var(--muted)">{{ $book->description }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- ── Related ──────────────────────────────────────────────── --}}
@if($related->isNotEmpty())
<section class="py-16" style="background:var(--bg-alt)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-extrabold mb-8" style="color:var(--text)">Buku Lainnya</h2>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($related as $rel)
            <a href="{{ route('books.show', $rel) }}"
               class="fi-card fi-card-hover flex flex-col overflow-hidden group">
                <div class="relative overflow-hidden h-44">
                    <img src="{{ $rel->cover_url }}" alt="{{ $rel->title }}"
                         loading="lazy"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                </div>
                <div class="p-4">
                    <h3 class="text-sm font-bold line-clamp-2 mb-1" style="color:var(--text)">{{ $rel->title }}</h3>
                    <p class="text-sm font-extrabold" style="color:var(--primary)">{{ $rel->formatted_price }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
