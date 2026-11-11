{{--
    Meta Pixel (iklan Facebook & Instagram) — di dalam <head>, setelah GTM.

    Yang dikirim hanya event yang ditulis di sini:
    - PageView di setiap halaman publik;
    - event antrean dari request sebelumnya (Lead saat pendaftaran PPDB tersimpan);
    - Contact saat tautan chat WhatsApp diklik.
    autoConfig dimatikan supaya pixel tidak merekam klik tombol dan isi halaman
    dengan sendirinya.

    Halaman yang URL-nya rahasia (status PPDB bertanda tangan, ditandai lewat
    $seo['referrer']) tidak memuat script ini, karena pixel mengirim URL halaman
    lengkap ke Meta. Event antrean untuk halaman seperti itu dikirim lewat
    partials/meta-pixel-body.blade.php.
--}}
@if(empty($seo['referrer']) && \App\Support\MetaPixel::enabled())
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');

        fbq('set', 'autoConfig', false, '{{ \App\Support\MetaPixel::pixelId() }}');
        fbq('init', '{{ \App\Support\MetaPixel::pixelId() }}');
        fbq('track', 'PageView');
        @foreach(\App\Support\MetaPixel::queuedEvents() as $event)
        fbq('track', @json($event['name']), @json($event['parameters']));
        @endforeach

        (function () {
            // Tautan chat (wa.me/62…, api.whatsapp.com/send?phone=…) dihitung;
            // tautan bagikan (wa.me/?text=…) dan undangan grup tidak, karena
            // tidak menghubungi sekolah.
            function opensWhatsAppChat(href) {
                var url;

                try {
                    url = new URL(href, window.location.href);
                } catch (error) {
                    return false;
                }

                var host = url.hostname.replace(/^www\./, '');

                if (host === 'wa.me') {
                    return url.pathname.replace(/\//g, '') !== '';
                }

                if (url.protocol === 'whatsapp:' || ['api.whatsapp.com', 'web.whatsapp.com', 'whatsapp.com'].indexOf(host) !== -1) {
                    return Boolean(url.searchParams.get('phone')) || url.pathname.indexOf('/message/') === 0;
                }

                return false;
            }

            // Fase capture, supaya tetap terhitung walau tautannya memanggil
            // stopPropagation() (mis. tombol WA di kartu guru).
            document.addEventListener('click', function (event) {
                var link = event.target instanceof Element ? event.target.closest('a[href]') : null;

                if (link && opensWhatsAppChat(link.getAttribute('href'))) {
                    fbq('track', 'Contact', {content_category: 'WhatsApp'});
                }
            }, true);
        })();
    </script>
@endif
