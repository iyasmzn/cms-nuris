{{--
    Kepala halaman gaya "Hero Landing" untuk Halaman Statis & Program: hero
    besar seperti halaman depan — label kecil, judul, sorotan, deskripsi, dan
    tombol — di atas Cover Hero halaman itu (gambar, berkas video, atau YouTube).

    Params:
      $landingHero — App\Support\LandingHero
      $heroCover   — App\Support\PageHero, media cover halaman
      $poster      — opsional, gambar cadangan bila cover tidak punya media
                     sendiri (mis. gambar program)
--}}
@php
    $poster = $poster ?? null;
@endphp

@once
<style>
    .landing-hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        background: var(--hero-band);
    }
    .landing-hero-md   { padding: 8rem 0 4rem; }
    .landing-hero-lg   { min-height: 32.5rem; padding: 7rem 0 4rem; }
    .landing-hero-full { min-height: 100vh; min-height: 100svh; padding: 7rem 0 4rem; }
    @media (min-width: 640px) {
        .landing-hero-md { padding: 10rem 0 6rem; }
        .landing-hero-lg { min-height: 36.25rem; }
    }
    @media (min-width: 1024px) {
        .landing-hero-lg { min-height: 40rem; }
    }

    /* Perataan isi hero, seperti pilihan perataan slide halaman depan */
    .landing-hero-copy { max-width: 42rem; }
    .landing-hero-align-center .landing-hero-copy { max-width: 48rem; margin-inline: auto; text-align: center; }
    .landing-hero-align-center .landing-hero-lead { margin-inline: auto; }
    .landing-hero-align-center .landing-hero-actions { justify-content: center; }
    .landing-hero-align-right .landing-hero-copy { margin-left: auto; text-align: right; }
    .landing-hero-align-right .landing-hero-lead { margin-left: auto; }
    .landing-hero-align-right .landing-hero-actions { justify-content: flex-end; }
</style>
@endonce

<section class="landing-hero page-band -mt-17 landing-hero-{{ $landingHero->size }} landing-hero-align-{{ $landingHero->align }}">
    @if($heroCover->hasMedia() || filled($poster))
        <x-page-hero-media :hero="$heroCover" :poster="$poster" :title="$landingHero->title" />
    @else
        <x-hero-pattern />
    @endif

    {{-- Pembungkusnya meneruskan klik ke cover (pop-up video), isinya tetap bisa diklik --}}
    <div class="relative z-10 w-full pointer-events-none">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="landing-hero-copy text-white pointer-events-auto" data-aos="fade-up">
                @if($landingHero->badge !== '')
                    <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 backdrop-blur-md px-4 py-1.5 rounded-full mb-6">
                        <span class="w-2 h-2 rounded-full animate-pulse" style="background:var(--primary-400)"></span>
                        <span class="text-white/80 text-xs font-semibold tracking-wider uppercase">{{ $landingHero->badge }}</span>
                    </div>
                @endif

                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.08] tracking-tight mb-5">
                    {{ $landingHero->title }}
                    @if($landingHero->highlight !== '')
                        <span class="block" style="color:var(--primary-300)">{{ $landingHero->highlight }}</span>
                    @endif
                </h1>

                @if($landingHero->subtitle !== '')
                    <p class="landing-hero-lead text-white/75 text-base sm:text-lg leading-relaxed max-w-xl font-light">
                        {{ $landingHero->subtitle }}
                    </p>
                @endif

                @if($landingHero->buttons !== [])
                    <div class="landing-hero-actions mt-8 flex flex-wrap gap-3">
                        @foreach($landingHero->buttons as $button)
                            <a href="{{ $button['url'] }}"
                               @if($button['external']) target="_blank" rel="noopener" @endif
                               @if($button['primary'])
                                   class="inline-flex items-center gap-2 px-7 py-3.5 rounded-2xl font-bold hover:opacity-90 transition-all shadow-xl text-sm"
                                   style="background:var(--primary-400);color:var(--primary-900)"
                               @else
                                   class="inline-flex items-center gap-2 px-7 py-3.5 rounded-2xl bg-white/10 border border-white/25 backdrop-blur-sm font-semibold hover:bg-white/20 transition-all text-sm"
                               @endif>
                                {{ $button['label'] }}
                                @if($button['primary'])
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
