@php
    $galleryMedia ??= collect();
@endphp

<x-section-background :config="section_background('section_gallery')"
                     background="var(--bg)"
                     id="galeri" class="py-20 sm:py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        @php
            $eyebrow  = setting('section_gallery_eyebrow', 'Foto & Video');
            $subtitle = setting('section_gallery_subtitle', 'Momen-momen berharga dari kehidupan sekolah kami.');
        @endphp
        <div class="flex items-end justify-between gap-6 mb-14" data-aos="fade-up">
            <div>
                @if($eyebrow)
                    <div class="fi-label mb-3">{{ $eyebrow }}</div>
                @endif
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight" style="color:var(--text)">{{ setting('section_gallery_title') ?: 'Galeri Sekolah' }}</h2>
                @if($subtitle)
                    <p class="mt-2 text-base max-w-md leading-relaxed" style="color:var(--muted)">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>
            <a href="{{ route('gallery.index') }}"
               class="shrink-0 inline-flex items-center gap-1.5 text-sm font-semibold transition-colors hover:opacity-75"
               style="color:var(--primary)">
                Semua Foto
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        @if($galleryMedia->isEmpty())
            <div class="rounded-2xl border border-dashed py-16 text-center" style="border-color:var(--border)" data-aos="fade-up">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 text-3xl" style="background:var(--card)">🖼️</div>
                <p class="text-sm font-semibold" style="color:var(--text)">Belum ada foto di galeri</p>
                <p class="text-xs mt-1" style="color:var(--muted)">Tandai media dengan "Tampil di Galeri" dari panel admin.</p>
            </div>
        @else
            @include('sections.bodies.gallery', ['galleryMedia' => $galleryMedia])
        @endif
    </div>
</x-section-background>
