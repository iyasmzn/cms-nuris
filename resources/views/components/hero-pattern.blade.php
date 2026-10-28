{{--
    Pola penghias pita kepala halaman. Dipasang sebagai anak pertama elemen
    ber-`.page-band`; isi pitanya sendiri harus berada di atasnya (`relative`),
    persis seperti hero subhalaman yang sudah memakai `relative z-10`.

    Polanya dilukis sebagai *mask*, bukan gambar: yang terlihat adalah warna
    pilihan admin di baliknya. Satu setting di Tema & Tampilan berlaku untuk
    seluruh pita di situs.
--}}
@php
    $heroPattern = \App\Support\HeroPattern::fromSettings();
@endphp

@if($heroPattern->isActive())
    @once
        <style>
            .hero-pattern {
                position: absolute;
                inset: 0;
                z-index: 0;
                pointer-events: none;
                background-color: var(--hero-pattern-color);
                -webkit-mask-image: var(--hero-pattern);
                mask-image: var(--hero-pattern);
                -webkit-mask-size: var(--hero-pattern-size);
                mask-size: var(--hero-pattern-size);
                -webkit-mask-repeat: repeat;
                mask-repeat: repeat;
                opacity: var(--hero-pattern-opacity);
            }
        </style>
    @endonce

    <div class="hero-pattern"
         aria-hidden="true"
         style="--hero-pattern:{{ $heroPattern->maskUrl() }};
                --hero-pattern-color:{{ $heroPattern->colorCss() }};
                --hero-pattern-size:{{ $heroPattern->tileSize() }};
                --hero-pattern-opacity:{{ $heroPattern->opacityValue() }}"></div>
@endif
