@props([
    'title',
    'description' => null,
    'noindex' => false,
])

@php
    $description ??= 'Pantau kondisi tanaman secara realtime: kelembaban tanah, air tandon (float switch), suhu & kelembaban udara, serta penyiraman otomatis berbasis ESP32.';
    $siteName = 'Taman Pintar IoT';
    $fullTitle = $title.' · '.$siteName;
    $canonical = url()->current();
@endphp

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $noindex ? 'noindex, nofollow' : 'index, follow' }}">
<meta name="theme-color" content="#1e3a8a">
<link rel="canonical" href="{{ $canonical }}">

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
<meta name="application-name" content="{{ $siteName }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Taman Pintar">
<meta name="format-detection" content="telephone=no">

@unless ($noindex)
    <meta name="keywords" content="IoT, smart irrigation, penyiram tanaman otomatis, ESP32, sensor kelembaban tanah, float switch, DHT11, monitoring tanaman">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">

    <meta property="og:image" content="{{ asset('icon-512.png') }}">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:image" content="{{ asset('icon-512.png') }}">
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ $description }}">

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => url('/'),
            'description' => $description,
            'inLanguage' => 'id-ID',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
@endunless
