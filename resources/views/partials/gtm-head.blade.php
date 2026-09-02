{{--
    Google Tag Manager — dipasang setinggi mungkin di dalam <head>.

    Halaman yang URL-nya sendiri adalah rahasia (status PPDB bertanda tangan)
    menandai dirinya lewat $seo['referrer']. GTM mengirim URL halaman lengkap ke
    server Google, jadi halaman seperti itu sengaja tidak ikut di-track supaya
    signed URL-nya tidak bocor ke pihak ketiga.
--}}
@if(empty($seo['referrer']) && \App\Support\GoogleTagManager::enabled())
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ \App\Support\GoogleTagManager::containerId() }}');
    </script>
@endif
