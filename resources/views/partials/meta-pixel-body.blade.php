{{-- Meta Pixel tanpa script — tepat setelah <body>, pasangan partials/meta-pixel-head.blade.php. --}}
@if(\App\Support\MetaPixel::enabled())
    @if(empty($seo['referrer']))
        <noscript><img height="1" width="1" style="display:none" alt=""
            src="{{ \App\Support\MetaPixel::noscriptPageViewUrl() }}"></noscript>
    @else
        {{--
            Halaman bertanda tangan tidak memuat script pixel. Pendaftaran yang
            dialihkan ke sini tetap dilaporkan sebagai gambar 1px yang membawa URL
            halaman PPDB jenjangnya (bukan URL rahasia halaman ini) dan tidak
            mengirim Referer.
        --}}
        @foreach(\App\Support\MetaPixel::queuedEvents() as $event)
            <img height="1" width="1" style="display:none" alt="" referrerpolicy="no-referrer"
                src="{{ \App\Support\MetaPixel::imagePixelUrl($event, request()) }}">
        @endforeach
    @endif
@endif
