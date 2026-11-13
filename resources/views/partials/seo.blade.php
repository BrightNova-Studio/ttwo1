{{-- Search + social metadata for a public page. Expects $seoTitle, $seoDesc, $seoPath; optional $seoJson (array of JSON-LD objects). --}}
@php
    $base = rtrim(config('app.url'), '/');
    $url = $base.($seoPath === '/' ? '/' : $seoPath);
    $img = $base.'/og.png';
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDesc }}">
<link rel="canonical" href="{{ $url }}">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
<meta property="og:type" content="website">
<meta property="og:site_name" content="1two1">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:image" content="{{ $img }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="1two1: private, encrypted chat rooms for two people">
<meta property="og:locale" content="en_US">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDesc }}">
<meta name="twitter:image" content="{{ $img }}">
@foreach (($seoJson ?? []) as $ld)
<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endforeach
