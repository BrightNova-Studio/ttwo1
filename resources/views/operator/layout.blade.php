<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="dark">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', 'Console') · 1two1</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='9' fill='%2308080c'/%3E%3Ccircle cx='12.5' cy='16' r='7' fill='none' stroke='%238b7bff' stroke-width='2.4'/%3E%3Ccircle cx='19.5' cy='16' r='7' fill='none' stroke='%2346b0ff' stroke-width='2.4'/%3E%3C/svg%3E">
    @vite(['resources/css/operator.css', 'resources/js/operator.js'])
</head>
<body>
<div class="ambient" aria-hidden="true"><i class="orb o1"></i><i class="orb o2"></i></div>

@auth
<header class="bar">
    <a class="brand" href="{{ route('operator.dashboard') }}">
        <span class="mark" aria-hidden="true"><i></i><i></i></span>
        <span class="word">1two1</span><span class="tag">Console</span>
    </a>
    <nav>
        <a href="{{ route('operator.dashboard') }}" @class(['on' => request()->routeIs('operator.dashboard')])>Rooms</a>
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
