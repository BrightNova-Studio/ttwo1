<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#06060a">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <meta name="description" content="1two1 opens a private, end-to-end encrypted room for exactly two people. Temp rooms vanish 30 minutes after you both leave.">
    <title>@yield('title') · 1two1</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @vite(['resources/css/info.css'])
</head>
<body>
<div class="ambient" aria-hidden="true"><i class="orb o1"></i><i class="orb o2"></i></div>

<header class="top">
    <a class="brand" href="/" aria-label="1two1 home">@include('partials.brand', ['u' => 'i'])</a>
    <nav aria-label="Learn more">
        <a href="/how-it-works" @class(['on' => request()->is('how-it-works')])>How it works</a>
        <a href="/temp-vs-long" @class(['on' => request()->is('temp-vs-long')])>Temp vs Long</a>
        <a href="/privacy" @class(['on' => request()->is('privacy')])>Privacy</a>
        <a href="/faq" @class(['on' => request()->is('faq')])>FAQ</a>
    </nav>
    <a class="cta" href="/">Open a room</a>
</header>

<main class="doc">
    @yield('content')

    <section class="end">
        <h2>Ready when you are</h2>
        <p>No account. No app. Open a room, send the key to one person, and talk.</p>
        <a class="cta big" href="/">Create a secret room</a>
    </section>
</main>

<footer class="foot">
    <span>1two1 &middot; secret rooms for two</span>
    <span><a href="/how-it-works">How it works</a> &middot; <a href="/temp-vs-long">Temp vs Long</a> &middot; <a href="/privacy">Privacy</a> &middot; <a href="/faq">FAQ</a> &middot; <a href="mailto:{{ $supportEmail }}">Contact</a></span>
</footer>
</body>
</html>
