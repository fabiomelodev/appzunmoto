{{--
    PWA install metadata: the manifest (name, icons, standalone display) plus
    the Apple-specific tags Safari/iOS still needs on top of it (iOS ignores
    several manifest fields and relies on these meta tags instead — this is
    also a hard requirement for Web Push to work at all on iOS, which only
    delivers push to a site installed as a PWA).
--}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}" />
<meta name="theme-color" content="#0e0e10" />
<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
<meta name="apple-mobile-web-app-title" content="ZunMoto" />
<link rel="apple-touch-icon" href="{{ asset('assets/icon-192.png') }}" />
