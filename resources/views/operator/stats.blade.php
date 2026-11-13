@extends('operator.layout')
@section('title', 'Statistics')
@section('content')

@php
    $ranges = ['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', '90d' => 'Last 90 days', '365d' => 'Last year'];
    $max = max(1, collect($series)->max(fn ($s) => max($s['created'], $s['expired'], $s['removed'])));
    $n = max(1, count($series));
    $W = 720; $H = 190; $pad = 26;
    $slot = ($W - 2 * $pad) / $n;
    $bw = max(2, min(14, $slot / 3.4));
@endphp

<div class="head">
    <div>
        <h1>Statistics</h1>
        <p class="muted">{{ $from->format('d M Y') }} &ndash; {{ $to->format('d M Y') }} ({{ config('app.timezone') }}). Counts only: nothing here reveals who was in a room or what was said.</p>
    </div>
</div>

<nav class="ranges" aria-label="Period">
    @foreach ($ranges as $k => $l)
        <a href="{{ route('operator.stats', ['range' => $k]) }}" @class(['on' => $range === $k])>{{ $l }}</a>
    @endforeach
</nav>
<form method="get" action="{{ route('operator.stats') }}" class="custom">
    <input type="hidden" name="range" value="custom">
    <label>From<input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ now()->toDateString() }}" required></label>
    <label>To<input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ now()->toDateString() }}" required></label>
    <button class="btn ghost" type="submit">Show custom period</button>
</form>
@error('to')<p class="err">{{ $message }}</p>@enderror

<h3 class="sec">In this period</h3>
<div class="stats">
    <div class="stat"><b>{{ $period['created'] }}</b><span>Rooms created</span></div>
    <div class="stat"><b>{{ $period['created_temp'] }}</b><span>Temp rooms created</span></div>
    <div class="stat"><b>{{ $period['created_long'] }}</b><span>Long-term created</span></div>
    <div class="stat"><b>{{ $period['expired'] }}</b><span>Auto-expired</span></div>
    <div class="stat"><b>{{ $period['expired_temp'] }}</b><span>Temp expired</span></div>
    <div class="stat"><b>{{ $period['expired_long'] }}</b><span>Long-term expired</span></div>
    <div class="stat"><b>{{ $period['created_self'] }}</b><span>Opened by members</span></div>
    <div class="stat"><b>{{ $period['removed'] }}</b><span>Deleted by operator</span></div>
    <div class="stat"><b>{{ $period['created_random'] }}</b><span>Random matches</span></div>
    <div class="stat"><b>{{ $period['ended'] }}</b><span>Ended by a member</span></div>
    <div class="stat"><b>{{ $period['reported'] }}</b><span>Reported by a member</span></div>
</div>

<div class="panel pad chart">
    <div class="legend"><span><i class="k1"></i>Created</span><span><i class="k2"></i>Auto-expired</span><span><i class="k3"></i>Deleted by operator</span></div>
    <svg viewBox="0 0 {{ $W }} {{ $H + 26 }}" role="img" aria-label="Rooms created and expired per {{ $byMonth ? 'month' : 'day' }}">
        @foreach ([0.25, 0.5, 0.75, 1] as $g)
            <line x1="{{ $pad }}" x2="{{ $W - $pad }}" y1="{{ round($H - $g * ($H - 14), 1) }}" y2="{{ round($H - $g * ($H - 14), 1) }}" class="grid"/>
        @endforeach
        <text x="2" y="{{ 14 }}" class="axis">{{ $max }}</text>
        @foreach ($series as $i => $s)
            @php $cx = $pad + $slot * $i + $slot / 2; @endphp
            @foreach (['created' => 'k1', 'expired' => 'k2', 'removed' => 'k3'] as $key => $cls)
                @php
                    $h = round($s[$key] / $max * ($H - 14), 1);
                    $off = ['created' => -1, 'expired' => 0, 'removed' => 1][$key] * ($bw + 1);
                @endphp
                @if ($h > 0)<rect x="{{ round($cx + $off - $bw / 2, 1) }}" y="{{ round($H - $h, 1) }}" width="{{ round($bw, 1) }}" height="{{ $h }}" rx="2" class="{{ $cls }}"><title>{{ $s['label'] }}: {{ $s[$key] }} {{ $key }}</title></rect>@endif
            @endforeach
            @if ($n <= 12 || $i % (int) ceil($n / 10) === 0)
                <text x="{{ round($cx, 1) }}" y="{{ $H + 18 }}" text-anchor="middle" class="axis">{{ $s['label'] }}</text>
            @endif
        @endforeach
        <line x1="{{ $pad }}" x2="{{ $W - $pad }}" y1="{{ $H }}" y2="{{ $H }}" class="base"/>
    </svg>
</div>

<h3 class="sec">Right now</h3>
<div class="stats">
    <div class="stat"><b>{{ $now['active'] + $now['blocked'] }}</b><span>Rooms open</span></div>
    <div class="stat"><b>{{ $now['temp'] }}</b><span>Temp rooms</span></div>
    <div class="stat"><b>{{ $now['long'] }}</b><span>Long-term rooms</span></div>
    <div class="stat live"><b>{{ $now['live'] }}</b><span>Someone online</span></div>
    <div class="stat"><b>{{ $now['paired'] }}</b><span>Both members joined</span></div>
    <div class="stat"><b>{{ $now['locked'] }}</b><span>Secret phrase set</span></div>
    <div class="stat"><b>{{ $now['random'] }}</b><span>Random-connect rooms</span></div>
    <div class="stat live"><b>{{ $now['searching'] }}</b><span>Searching for a match</span></div>
    <div class="stat"><b>{{ $now['blocked'] }}</b><span>Blocked</span></div>
    <div class="stat"><b>{{ $ttl['temp'] / 60 }} min / {{ $ttl['long'] / 86400 }} d</b><span>Expiry after both leave</span></div>
</div>

<p class="muted small foot">Rooms that expire or are deleted are removed completely, with everything in them. Only these anonymous counters are kept.</p>
@endsection
