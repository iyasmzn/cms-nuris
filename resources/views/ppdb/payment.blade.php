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
    .rek-card { display: flex; align-items: flex-start; gap: .75rem; padding: .85rem 1rem; border-radius: .625rem; border: 1.5px solid var(--border); cursor: pointer; transition: all .15s; }
    .rek-card:has(input:checked) { border-color: #d97706; background: #fffbeb; }
    .rek-card input[type="radio"] { margin-top: .2rem; accent-color: #d97706; }
    .rek-card:hover { border-color: #fbbf24; }
    .info-row { display: flex; justify-content: space-between; gap: 1rem; padding: .6rem 0; border-bottom: 1px solid var(--border); font-size: .8125rem; }
    .info-row:last-child { border-bottom: 0; }
</style>
@endpush

@section('content')
@php
    $fmtDate = fn ($d) => $d ? $d->locale('id')->translatedFormat('d M Y') : '—';
    $fmtDateTime = fn ($d) => $d ? $d->locale('id')->translatedFormat('d M Y, H:i') : '—';

    $regStatusStyles = [
        'pending' => ['Menunggu Verifikasi', 'bg-amber-50 text-amber-700 border-amber-200'],
        'verified' => ['Terverifikasi', 'bg-blue-50 text-blue-700 border-blue-200'],
        'accepted' => ['Diterima', 'bg-green-50 text-green-700 border-green-200'],
        'rejected' => ['Tidak Diterima', 'bg-red-50 text-red-700 border-red-200'],
    ];
    [$regStatusLabel, $regStatusClass] = $regStatusStyles[$registration->status] ?? ['—', 'bg-gray-100 text-gray-600 border-gray-200'];

    $payStatusClasses = [
        \App\Models\RegistrationPayment::STATUS_UNPAID => 'bg-amber-50 text-amber-700 border-amber-200',
        \App\Models\RegistrationPayment::STATUS_WAITING => 'bg-blue-50 text-blue-700 border-blue-200',
        \App\Models\RegistrationPayment::STATUS_PAID => 'bg-green-50 text-green-700 border-green-200',
        \App\Models\RegistrationPayment::STATUS_REJECTED => 'bg-red-50 text-red-700 border-red-200',
        \App\Models\RegistrationPayment::STATUS_EXPIRED => 'bg-gray-100 text-gray-600 border-gray-200',
    ];
@endphp

<section class="ppdb-hero -mt-17 pt-32 pb-14 sm:pt-36 sm:pb-16">
    <x-hero-geo />
    <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 z-10 text-center" data-aos="fade-up">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 border border-amber-500/30 mb-5">
            <span class="text-xs font-bold text-amber-300 uppercase tracking-widest">No. {{ $registration->registration_number ?? '—' }}</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white leading-tight mb-3">
            {{ $registration->full_name }}
        </h1>
        <p class="text-white/70 text-sm">
            PPDB {{ $registration->institution?->name }}
            @if($registration->academicYear) · Tahun Ajaran {{ $registration->academicYear->label }} @endif
        </p>
    </div>
</section>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">

    @if(session('success'))
    <div class="flex gap-3 p-4 rounded-xl border border-green-200 bg-green-50 text-green-800 text-sm" data-aos="fade-up">
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <p>{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="flex gap-3 p-4 rounded-xl border border-red-200 bg-red-50 text-red-800 text-sm" data-aos="fade-up">
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p>{{ session('error') }}</p>
    </div>
    @endif

    {{-- ── Ringkasan pendaftaran ─────────────────────────────── --}}
    <div class="fi-card p-6" data-aos="fade-up">
        <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
            <h2 class="font-bold text-base" style="color:var(--text)">Status Pendaftaran</h2>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full border {{ $regStatusClass }}">{{ $regStatusLabel }}</span>
        </div>

        <div>
            <div class="info-row">
                <span style="color:var(--muted)">Nomor Pendaftaran</span>
                <span class="font-semibold" style="color:var(--text)">{{ $registration->registration_number ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span style="color:var(--muted)">Jenjang</span>
                <span class="font-semibold" style="color:var(--text)">{{ $registration->institution?->name ?? '—' }}</span>
            </div>
            @if($registration->registrationWave)
            <div class="info-row">
                <span style="color:var(--muted)">Gelombang</span>
                <span class="font-semibold" style="color:var(--text)">{{ $registration->registrationWave->name }}</span>
            </div>
            @endif
            @if($registration->admissionPath)
            <div class="info-row">
                <span style="color:var(--muted)">Jalur</span>
                <span class="font-semibold" style="color:var(--text)">{{ $registration->admissionPath->name }}</span>
            </div>
            @endif
            <div class="info-row">
                <span style="color:var(--muted)">Tanggal Daftar</span>
                <span class="font-semibold" style="color:var(--text)">{{ $fmtDateTime($registration->created_at) }}</span>
            </div>
        </div>
    </div>

    @if($payment)
    {{-- ── Tagihan ───────────────────────────────────────────── --}}
    <div class="fi-card overflow-hidden" data-aos="fade-up">
        <div class="p-6">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
                <div>
                    <h2 class="font-bold text-base" style="color:var(--text)">Biaya Pendaftaran</h2>
                    <p class="text-xs mt-0.5" style="color:var(--muted)">{{ $payment->invoice_number ?? '—' }}</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full border {{ $payStatusClasses[$payment->isExpired() ? \App\Models\RegistrationPayment::STATUS_EXPIRED : $payment->status] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
                    {{ $payment->isExpired() && ! $payment->isSettled() ? 'Kedaluwarsa' : $payment->statusLabel() }}
                </span>
            </div>

            <div class="p-5 rounded-xl border border-amber-200 bg-amber-50 text-center mb-5">
                <p class="text-xs font-semibold text-amber-800 mb-1">Total yang harus ditransfer</p>
                <p class="text-3xl font-extrabold text-amber-700">{{ rupiah($payment->total()) }}</p>
                @if($payment->unique_code > 0)
                <p class="text-xs text-amber-700 mt-2">
                    {{ rupiah($payment->amount) }} + {{ $payment->unique_code }} kode unik.
                    <strong>Transfer tepat sampai 3 digit terakhir</strong> agar pembayaran mudah dicocokkan.
                </p>
                @endif
            </div>

            @if($payment->expires_at && ! $payment->isSettled())
            <div class="info-row">
                <span style="color:var(--muted)">Batas Waktu Pembayaran</span>
                <span class="font-semibold {{ $payment->isExpired() ? 'text-red-600' : '' }}" style="{{ $payment->isExpired() ? '' : 'color:var(--text)' }}">{{ $fmtDateTime($payment->expires_at) }}</span>
            </div>
            @endif

            {{-- ── Sudah lunas ──────────────────────────────── --}}
            @if($payment->isSettled())
            <div class="mt-5 p-5 rounded-xl border border-green-200 bg-green-50 text-center">
                <div class="text-3xl mb-2">✅</div>
                <p class="font-bold text-green-800 text-sm mb-1">Pembayaran Lunas</p>
                <p class="text-xs text-green-700">Diverifikasi pada {{ $fmtDateTime($payment->verified_at) }}. Tidak ada tindakan lanjutan yang diperlukan.</p>
            </div>

            {{-- ── Menunggu verifikasi ──────────────────────── --}}
            @elseif($payment->status === \App\Models\RegistrationPayment::STATUS_WAITING)
            <div class="mt-5 p-5 rounded-xl border border-blue-200 bg-blue-50">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">⏳</span>
                    <div>
                        <p class="font-bold text-blue-800 text-sm mb-1">Bukti Transfer Sedang Diverifikasi</p>
                        <p class="text-xs text-blue-700">
                            Dikirim pada {{ $fmtDateTime($payment->submitted_at) }} atas nama <strong>{{ $payment->sender_name }}</strong>.
                            Panitia akan memverifikasi dalam 1×24 jam kerja. Anda tidak perlu mengirim ulang.
                        </p>
                    </div>
                </div>
            </div>

            {{-- ── Kedaluwarsa ──────────────────────────────── --}}
            @elseif($payment->isExpired())
            <div class="mt-5 p-5 rounded-xl border border-red-200 bg-red-50">
                <p class="font-bold text-red-800 text-sm mb-1">Batas Waktu Pembayaran Terlewat</p>
                <p class="text-xs text-red-700">Silakan hubungi panitia PPDB untuk mengaktifkan kembali tagihan ini.</p>
            </div>
            @endif

            @if($payment->status === \App\Models\RegistrationPayment::STATUS_REJECTED && $payment->note)
            <div class="mt-5 p-4 rounded-xl border border-red-200 bg-red-50">
                <p class="font-bold text-red-800 text-sm mb-1">Bukti transfer sebelumnya ditolak</p>
                <p class="text-xs text-red-700">{{ $payment->note }}</p>
                <p class="text-xs text-red-700 mt-1">Silakan unggah ulang bukti transfer yang benar.</p>
            </div>
            @endif
        </div>

        {{-- ── Instruksi + form unggah bukti ─────────────── --}}
        @if($proofUrl)
        <div class="border-t p-6" style="border-color:var(--border)">
            @if($instructions)
            <p class="text-sm mb-5" style="color:var(--muted)">{{ $instructions }}</p>
            @endif

            @if($errors->any())
            <div class="mb-5 p-4 rounded-xl border border-red-200 bg-red-50">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                    <li class="text-xs text-red-600">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ $proofUrl }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf

                @if($bankAccounts !== [])
                <div>
                    <label class="ppdb-label">Rekening Tujuan Transfer<span class="ppdb-required">*</span></label>
                    <div class="space-y-2.5">
                        @foreach($bankAccounts as $index => $account)
                        <label class="rek-card">
                            <input type="radio" name="bank_account" value="{{ $index }}" @checked((string) old('bank_account') === (string) $index)>
                            <span>
                                <span class="block text-sm font-bold" style="color:var(--text)">{{ $account['bank'] }} — {{ $account['number'] }}</span>
                                <span class="block text-xs" style="color:var(--muted)">a.n. {{ $account['holder'] }}</span>
                            </span>
                        </label>
                        @endforeach
                    </div>
                    @error('bank_account')<p class="ppdb-hint text-red-500">{{ $message }}</p>@enderror
                </div>
                @else
                <div class="p-4 rounded-xl border border-amber-200 bg-amber-50 text-xs text-amber-800">
                    Rekening tujuan belum diatur panitia. Hubungi panitia PPDB untuk memperoleh nomor rekening sebelum melakukan transfer.
                </div>
                @endif

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="ppdb-label" for="sender_name">Nama Pengirim<span class="ppdb-required">*</span></label>
                        <input type="text" id="sender_name" name="sender_name" class="ppdb-input @error('sender_name') border-red-400 @enderror"
                               value="{{ old('sender_name', $registration->parent_name ?? $registration->full_name) }}" placeholder="Nama pada rekening pengirim" required>
                        @error('sender_name')<p class="ppdb-hint text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="ppdb-label" for="transferred_on">Tanggal Transfer<span class="ppdb-required">*</span></label>
                        <input type="date" id="transferred_on" name="transferred_on" class="ppdb-input @error('transferred_on') border-red-400 @enderror"
                               value="{{ old('transferred_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                        @error('transferred_on')<p class="ppdb-hint text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="ppdb-label" for="upload-proof">Bukti Transfer<span class="ppdb-required">*</span></label>
                    <x-file-upload name="proof" required
                                   hint="Foto atau tangkapan layar struk transfer — JPG, PNG, atau PDF, maksimal 2 MB." />
                    @error('proof')<p class="ppdb-hint text-red-500">{{ $message }}</p>@enderror
                </div>

                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-7 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm transition-all">
                    Kirim Bukti Pembayaran
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </button>
            </form>
        </div>
        @endif
    </div>
    @endif

    {{-- ── Simpan tautan halaman ini ──────────────────────────── --}}
    <div class="fi-card p-5" data-aos="fade-up"
         x-data="{
            copied: false,
            url: @js($statusUrl),
            async copy() {
                try {
                    await navigator.clipboard.writeText(this.url);
                } catch (error) {
                    // The clipboard API needs a secure context (HTTPS). Fall
                    // back to selecting the field so the pendaftar can copy it
                    // manually instead of the button silently doing nothing.
                    this.$refs.field.select();
                    this.$refs.field.setSelectionRange(0, 99999);
                    document.execCommand('copy');
                }

                this.copied = true;
                setTimeout(() => this.copied = false, 2500);
            },
         }">
        <div class="flex items-start gap-3 mb-3">
            <span class="shrink-0 w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m6.5-6.5l1.5-1.5a4 4 0 115.656 5.656l-3 3a4 4 0 01-5.656 0"/></svg>
            </span>
            <div>
                <h2 class="font-bold text-sm" style="color:var(--text)">Simpan Tautan Halaman Ini</h2>
                <p class="text-xs mt-1 leading-relaxed" style="color:var(--muted)">
                    Tautan ini khusus untuk pendaftaran Anda. Simpan atau kirim ke WhatsApp sendiri agar bisa
                    kembali memeriksa status dan mengunggah bukti pembayaran tanpa mengisi data lagi.
                    <strong>Jangan bagikan ke orang lain</strong> — siapa pun yang memegangnya bisa melihat data pendaftaran Anda.
                </p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-2">
            <input type="text" x-ref="field" :value="url" readonly
                   x-on:focus="$event.target.select()"
                   class="ppdb-input flex-1 text-xs font-mono"
                   aria-label="Tautan halaman status pendaftaran">

            <button type="button" x-on:click="copy()"
                    class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
                    :class="copied ? 'bg-green-500 text-white' : 'bg-amber-500 hover:bg-amber-600 text-white'">
                <template x-if="! copied">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Salin Tautan
                    </span>
                </template>
                <template x-if="copied">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        Tersalin!
                    </span>
                </template>
            </button>
        </div>
    </div>

    <p class="text-center text-xs" style="color:var(--muted)">
        Tautan hilang? Buka kembali lewat
        <a href="{{ route('ppdb.status') }}" class="text-amber-600 font-semibold underline">Cek Status Pendaftaran</a>
        dengan nomor form pendaftaran dan nomor HP.
    </p>
</div>
@endsection
