{{--
    Satu kartu milik seksi dinamis, dipakai baik oleh deretan kartu maupun
    carousel. Kartu bertombol menampilkan CTA-nya; kartu yang hanya punya
    tautan (tanpa label tombol) dibuat bisa diklik seluruh kartunya.
--}}
@props(['card'])

<div class="cs-card fi-card fi-card-hover">

    @if($card->is_clickable)
        <a href="{{ $card->cta_url }}"
           class="cs-card-link"
           @if($card->cta_new_tab) target="_blank" rel="noopener noreferrer" @endif>
            <span class="sr-only">{{ $card->title }}</span>
        </a>
    @endif

    @if($card->image_url)
        <div class="cs-card-media">
            <img src="{{ $card->image_url }}"
                 alt="{{ $card->title }}"
                 loading="lazy"
                 class="w-full h-full object-cover">
        </div>
    @endif

    <div class="cs-card-body">
        @if($card->title)
            <h3 class="cs-card-title">{{ $card->title }}</h3>
        @endif

        @if($card->description)
            <p class="cs-card-text">{{ $card->description }}</p>
        @endif

        @if($card->has_cta)
            <a href="{{ $card->cta_url }}"
               class="cs-card-cta"
               @if($card->cta_new_tab) target="_blank" rel="noopener noreferrer" @endif>
                {{ $card->cta_label }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        @endif
    </div>
</div>
