{{-- Google Tag Manager (noscript) — harus tepat setelah <body>. Kondisinya
     sama persis dengan partials/gtm-head.blade.php. --}}
@if(empty($seo['referrer']) && \App\Support\GoogleTagManager::enabled())
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ \App\Support\GoogleTagManager::containerId() }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
