@extends('layouts.public')

@php
$seo = [
    'title' => 'Keranjang Belanja | '.setting('site_name', config('app.name')),
    'description' => 'Keranjang belanja buku Anda.',
    'canonical' => route('cart.index'),
];
@endphp

@section('content')

{{-- ── Header ─────────────────────────────────────────────── --}}
<div style="background:linear-gradient(135deg,#082828 0%,#08484A 100%)" class="-mt-17 pt-30 pb-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-1.5 rounded-full mb-4"
             style="background:rgba(255,255,255,.12);color:rgba(255,255,255,.9);border:1px solid rgba(255,255,255,.2)">
            🛒 Keranjang Belanja
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white">Keranjang Saya</h1>
    </div>
</div>

<section class="py-14" style="background:var(--bg)">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-6 px-4 py-3 rounded-xl text-sm font-medium text-green-800 bg-green-50 border border-green-200">
                {{ session('success') }}
            </div>
        @endif

        @if($items->isEmpty())
            {{-- Empty cart --}}
            <div class="fi-card text-center py-24">
                <div class="text-6xl mb-4">🛒</div>
                <h2 class="text-xl font-bold mb-2" style="color:var(--text)">Keranjang Masih Kosong</h2>
                <p class="text-sm mb-6" style="color:var(--muted)">Belum ada buku yang ditambahkan ke keranjang.</p>
                <a href="{{ route('books.index') }}" class="btn-primary">Lihat Koleksi Buku</a>
            </div>
        @else
            <div class="grid lg:grid-cols-3 gap-8">

                {{-- Items --}}
                <div class="lg:col-span-2 space-y-4">
                    @foreach($items as $item)
                    @php /** @var \App\Models\Book $book */ $book = $item['book']; @endphp
                    <div class="fi-card p-5 flex gap-5">

                        {{-- Cover --}}
                        <a href="{{ route('books.show', $book) }}"
                           class="shrink-0 w-20 rounded-xl overflow-hidden" style="aspect-ratio:3/4">
                            <img src="{{ $book->cover_url }}" alt="{{ $book->title }}"
                                 class="w-full h-full object-cover">
                        </a>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('books.show', $book) }}"
                               class="font-bold text-sm leading-snug hover:opacity-70 transition-opacity line-clamp-2"
                               style="color:var(--text)">{{ $book->title }}</a>
                            @if($book->author)
                                <p class="text-xs mt-0.5" style="color:var(--muted)">{{ $book->author }}</p>
                            @endif
                            <p class="font-extrabold text-sm mt-2" style="color:var(--primary)">
                                {{ $book->formatted_price }}
                            </p>

                            {{-- Qty update --}}
                            <form method="POST" action="{{ route('cart.update', $book) }}" class="flex items-center gap-2 mt-3">
                                @csrf
                                @method('PATCH')
                                <label class="text-xs font-semibold" style="color:var(--muted)">Jumlah:</label>
                                <input type="number" name="qty" value="{{ $item['qty'] }}"
                                       min="1" max="{{ $book->stock }}"
                                       class="w-16 px-2 py-1 text-sm rounded-lg border text-center"
                                       style="border-color:var(--border);background:var(--bg);color:var(--text)">
                                <button type="submit"
                                        class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors"
                                        style="background:var(--primary-50);color:var(--primary)">
                                    Update
                                </button>
                            </form>
                        </div>

                        {{-- Subtotal + remove --}}
                        <div class="shrink-0 flex flex-col items-end justify-between">
                            <span class="font-extrabold text-base" style="color:var(--text)">
                                Rp {{ number_format($book->price * $item['qty'], 0, ',', '.') }}
                            </span>
                            <form method="POST" action="{{ route('cart.remove', $book) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700 transition-colors">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach

                    {{-- Clear cart --}}
                    <div class="flex justify-between items-center pt-2">
                        <a href="{{ route('books.index') }}" class="btn-outline text-sm py-2 px-4">← Lanjut Belanja</a>
                        <form method="POST" action="{{ route('cart.clear') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-semibold transition-colors">
                                Kosongkan Keranjang
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="lg:col-span-1">
                    <div class="fi-card p-6 sticky top-24">
                        <h2 class="font-extrabold text-lg mb-5" style="color:var(--text)">Ringkasan Pesanan</h2>

                        <div class="space-y-3 mb-5">
                            @foreach($items as $item)
                            @php /** @var \App\Models\Book $book */ $book = $item['book']; @endphp
                            <div class="flex justify-between text-sm gap-3">
                                <span class="line-clamp-1 flex-1" style="color:var(--muted)">
                                    {{ $book->title }} ×{{ $item['qty'] }}
                                </span>
                                <span class="shrink-0 font-medium" style="color:var(--text)">
                                    Rp {{ number_format($book->price * $item['qty'], 0, ',', '.') }}
                                </span>
                            </div>
                            @endforeach
                        </div>

                        <div class="pt-4 border-t flex justify-between font-extrabold text-lg mb-6"
                             style="border-color:var(--border)">
                            <span style="color:var(--text)">Total</span>
                            <span style="color:var(--primary)">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>

                        @unless(feature_enabled('toko_checkout'))
                            <div class="p-4 rounded-xl text-sm text-center"
                                 style="background:var(--primary-50);color:var(--primary-800)">
                                Checkout sedang tidak tersedia saat ini.
                            </div>
                        @else
                            @guest
                                <div class="p-4 rounded-xl mb-4 text-sm text-center"
                                     style="background:var(--primary-50);color:var(--primary-800)">
                                    Silakan masuk terlebih dahulu untuk melanjutkan checkout.
                                </div>
                                <a href="{{ route('login') }}" class="btn-primary w-full justify-center mb-2">
                                    Masuk untuk Checkout
                                </a>
                                <a href="{{ route('register') }}" class="btn-outline w-full justify-center text-sm">
                                    Daftar Akun Baru
                                </a>
                            @else
                                <a href="{{ route('checkout.index') }}" class="btn-primary w-full justify-center">
                                    Lanjut ke Checkout
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                </a>
                            @endguest
                        @endunless
                    </div>
                </div>

            </div>
        @endif
    </div>
</section>

@endsection
