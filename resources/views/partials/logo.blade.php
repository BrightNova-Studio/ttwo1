{{-- App tile: two rings that overlap, with a keyhole where they meet. Two people, one locked room. --}}
@php $u = $u ?? 'g'; @endphp
<svg class="logo" viewBox="0 0 64 64" role="img" aria-label="1two1" focusable="false">
    <defs>
        <linearGradient id="{{ $u }}bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#2a1f6b"/><stop offset=".55" stop-color="#120f2c"/><stop offset="1" stop-color="#07070d"/></linearGradient>
        <linearGradient id="{{ $u }}a" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#c8bfff"/><stop offset="1" stop-color="#7461ff"/></linearGradient>
        <linearGradient id="{{ $u }}b" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8fdcff"/><stop offset="1" stop-color="#3a86ff"/></linearGradient>
        <radialGradient id="{{ $u }}g" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#9b8cff" stop-opacity=".55"/><stop offset="1" stop-color="#9b8cff" stop-opacity="0"/></radialGradient>
        <clipPath id="{{ $u }}l"><circle cx="23.5" cy="32" r="14"/></clipPath>
    </defs>
    <rect width="64" height="64" rx="15" fill="url(#{{ $u }}bg)"/>
    <rect x=".75" y=".75" width="62.5" height="62.5" rx="14.5" fill="none" stroke="#fff" stroke-opacity=".14" stroke-width="1.5"/>
    <circle cx="32" cy="32" r="22" fill="url(#{{ $u }}g)"/>
    <circle cx="23.5" cy="32" r="14" fill="none" stroke="url(#{{ $u }}a)" stroke-width="5"/>
    <circle cx="40.5" cy="32" r="14" fill="none" stroke="url(#{{ $u }}b)" stroke-width="5"/>
    <circle cx="40.5" cy="32" r="14" fill="#fff" fill-opacity=".22" clip-path="url(#{{ $u }}l)"/>
    <circle cx="32" cy="29.6" r="3.3" fill="#fff"/>
    <path d="M30.3 32h3.4l1.3 8.4h-6z" fill="#fff"/>
</svg>
