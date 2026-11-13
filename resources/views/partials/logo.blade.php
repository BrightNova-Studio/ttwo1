{{-- Two rings that overlap, with a keyhole where they meet: two people, one locked room. --}}
@php $u = $u ?? 'g'; @endphp
<svg class="logo" viewBox="0 0 64 40" role="img" aria-label="1two1" focusable="false">
    <defs>
        <linearGradient id="{{ $u }}a" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#b3a8ff"/><stop offset="1" stop-color="#6a55ff"/></linearGradient>
        <linearGradient id="{{ $u }}b" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#7fd2ff"/><stop offset="1" stop-color="#2f7bff"/></linearGradient>
        <clipPath id="{{ $u }}l"><circle cx="23" cy="20" r="15"/></clipPath>
    </defs>
    <circle cx="23" cy="20" r="15" fill="none" stroke="url(#{{ $u }}a)" stroke-width="4"/>
    <circle cx="41" cy="20" r="15" fill="none" stroke="url(#{{ $u }}b)" stroke-width="4"/>
    <circle cx="41" cy="20" r="15" fill="#fff" fill-opacity=".2" clip-path="url(#{{ $u }}l)"/>
    <circle cx="32" cy="17.8" r="2.8" fill="#fff"/>
    <path d="M30.6 20h2.8l1 7.2h-4.8z" fill="#fff"/>
</svg>
