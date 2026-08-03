@extends('layouts.public')

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
        background:
            radial-gradient(ellipse 70% 70% at 10% 50%, rgba(217,119,6,.25) 0%, transparent 55%),
            radial-gradient(ellipse 50% 50% at 90% 10%, rgba(251,191,36,.12) 0%, transparent 50%);
    }
    .ppdb-input {
        width: 100%;
        padding: .65rem .9rem;
        border-radius: .5rem;
        border: 1.5px solid var(--border);
        background: var(--card);
        color: var(--text);
        font-size: .875rem;
        transition: border-color .15s;
        outline: none;
    }
    .ppdb-input:focus { border-color: #d97706; box-shadow: 0 0 0 3px rgba(217,119,6,.12); }
    .ppdb-label { display: block; font-size: .8125rem; font-weight: 600; margin-bottom: .35rem; color: var(--text); }
    .ppdb-required { color: #ef4444; margin-left: .15rem; }
    .ppdb-hint { font-size: .7rem; color: var(--muted); margin-top: .25rem; }
</style>
@endpush

@section('content')

<section class="ppdb-hero -mt-17 pt-32 pb-14 sm:pt-36 sm:pb-16">
    <x-hero-geo />
    <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 z-10 text-center" data-aos="fade-up">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 border border-amber-500/30 mb-5">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            <span class="text-xs font-bold text-amber-300 uppercase tracking-widest">Cek Status</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white leading-tight mb-4">
            Status Pendaftaran &amp;<br><span class="text-amber-400">Pembayaran</span>
        </h1>
        <p class="text-white/70 text-sm sm:text-base leading-relaxed max-w-xl mx-auto">
            Masukkan nomor pendaftaran atau NIK, beserta nomor HP yang Anda isi pada formulir, untuk melihat status pendaftaran dan mengunggah bukti pembayaran.
        </p>
    </div>
</section>

<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    @if(session('error'))
    <div class="mb-6 flex gap-3 p-4 rounded-xl border border-red-200 bg-red-50 text-red-800 text-sm" data-aos="fade-up">
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p>{{ session('error') }}</p>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 p-4 rounded-xl border border-red-200 bg-red-50" data-aos="fade-up">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
            <li class="text-xs text-red-600">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('ppdb.status.find') }}" method="POST" class="fi-card p-6 space-y-5" data-aos="fade-up">
        @csrf

        <div>
            <label class="ppdb-label" for="identity">Nomor Pendaftaran atau NIK<span class="ppdb-required">*</span></label>
            <input type="text" id="identity" name="identity" class="ppdb-input"
                   value="{{ old('identity') }}" placeholder="SMP-2026-0001 atau 16 digit NIK" required>
            <p class="ppdb-hint">Lupa nomor pendaftaran? Isi NIK calon peserta saja.</p>
        </div>

        <div>
            <label class="ppdb-label" for="phone">Nomor HP<span class="ppdb-required">*</span></label>
            <input type="tel" id="phone" name="phone" class="ppdb-input"
                   value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" required>
            <p class="ppdb-hint">Nomor HP calon peserta atau orang tua / wali, sesuai yang diisi saat mendaftar.</p>
        </div>

        <button type="submit"
                class="w-full flex items-center justify-center gap-2 px-7 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm transition-all">
            Cek Status
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </button>
    </form>

    <p class="text-center text-xs mt-6" style="color:var(--muted)">
        Belum mendaftar? <a href="{{ route('ppdb.index') }}" class="text-amber-600 font-semibold underline">Buka halaman PPDB</a>
    </p>
</div>
@endsection
