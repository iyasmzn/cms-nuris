{{--
    Isi seksi Tahapan SPMB — empat langkah pertama beserta tombolnya, dipakai
    halaman depan (sections/spmb-steps) dan blok data di halaman & program.

    Params: $procedures — daftar langkah dari Pengaturan SPMB (atau bawaannya)
--}}
@php
    $ctaLabel     = setting('spmb_steps_cta_label', 'Lihat Detail & Daftar');
    $ctaUrl       = setting('spmb_steps_cta_url', '/ppdb');
    $stepsPreview = array_slice($procedures, 0, 4);
    $hasMore      = count($procedures) > 4;
@endphp

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
    @foreach($stepsPreview as $index => $step)
        <div class="fi-card fi-card-hover p-8 relative overflow-hidden"
             data-aos="fade-up" data-aos-delay="{{ $index * 100 }}">
            {{-- Step number watermark --}}
            <div class="absolute top-4 right-5 text-5xl font-black select-none leading-none"
                 style="color:var(--primary-100)">
                {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
            </div>
            @if($url = icon_url($step['icon_image'] ?? null))
                <img src="{{ $url }}" alt="{{ $step['title'] ?? '' }}" loading="lazy" class="w-9 h-9 mb-4 object-contain">
            @else
                <div class="text-3xl mb-4">{{ $step['icon'] ?? '📌' }}</div>
            @endif
            <div class="font-bold text-base mb-2.5" style="color:var(--text)">{{ $step['title'] ?? '' }}</div>
            <p class="text-sm leading-relaxed" style="color:var(--muted)">{{ $step['description'] ?? '' }}</p>
        </div>
    @endforeach
</div>

<div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4" data-aos="fade-up">
    <a href="{{ $ctaUrl }}" class="btn-primary text-base">
        {{ $ctaLabel }}
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
    </a>
    @if($hasMore)
        <a href="{{ route('ppdb.index') }}" class="btn-outline">
            Lihat {{ count($procedures) - 4 }} langkah lainnya
        </a>
    @endif
</div>
