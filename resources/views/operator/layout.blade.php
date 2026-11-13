<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="dark">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', 'Console') · 1two1</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @vite(['resources/css/operator.css', 'resources/js/operator.js'])
</head>
<body>
<div class="ambient" aria-hidden="true"><i class="orb o1"></i><i class="orb o2"></i></div>

@auth
<header class="bar">
    <a class="brand" href="{{ route('operator.dashboard') }}">
        @include('partials.brand', ['u' => 'o'])<span class="tag">Console</span>
    </a>
    <nav>
        <a href="{{ route('operator.dashboard') }}" @class(['on' => request()->routeIs('operator.dashboard')])>Rooms</a>
        <a href="{{ route('operator.stats') }}" @class(['on' => request()->routeIs('operator.stats')])>Statistics</a>
        <a href="{{ route('operator.logs') }}" @class(['on' => request()->routeIs('operator.logs')])>Activity</a>
        <a href="{{ route('operator.settings') }}" @class(['on' => request()->routeIs('operator.settings')])>Settings</a>
    </nav>
    <form method="post" action="{{ route('operator.logout') }}">@csrf<button class="btn ghost sm" type="submit">Sign out</button></form>
</header>
@endauth

<main class="wrap">
    @if (session('ok'))<div class="flash ok" role="status">{{ session('ok') }}</div>@endif
    @yield('content')
</main>
</body>
</html>
