{{--
    Facebook pixels: the ones in Admin > Configurações plus the product's own extra pixels.
    Params: $product (optional)
            $events = [['InitiateCheckout', ['value' => 17, 'currency' => 'BRL']], ...]
            $onceKey (optional): fires the events only once per browser session (e.g. Purchase).
--}}
@php
    $pixelIds = array_values(array_unique([
        ...\App\Models\Setting::facebookPixelIds(),
        ...(isset($product) ? $product->facebookPixelIds() : []),
    ]));
@endphp

@if (! empty($pixelIds))
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');

        @foreach ($pixelIds as $pixelId)
            fbq('init', @js($pixelId));
        @endforeach
        fbq('track', 'PageView');

        (function () {
            var onceKey = @js($onceKey ?? null);
            var events = @js($events ?? []);

            try {
                if (onceKey && sessionStorage.getItem(onceKey)) {
                    return;
                }
                if (onceKey) {
                    sessionStorage.setItem(onceKey, '1');
                }
            } catch (error) {}

            events.forEach(function (event) {
                fbq('track', event[0], event[1] || {});
            });
        })();
    </script>
    <noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ $pixelIds[0] }}&ev=PageView&noscript=1"></noscript>
@endif
