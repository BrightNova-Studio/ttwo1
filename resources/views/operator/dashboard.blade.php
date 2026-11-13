@extends('operator.layout')
@section('title', 'Rooms')
@section('content')

@php
    $c = session('credentials');
@endphp
@if ($c)
<div class="overlay" id="cred" role="dialog" aria-modal="true">
    <div class="dialog wide">
        <h2>{{ $c['title'] }}</h2>
        <p class="warnbox"><b>Shown only once.</b> These values are stored as one-way hashes. Once you close this, nobody can look them up again, including you. Copy them now and send them privately.</p>
        @if ($c['label'])<p class="muted">For: {{ $c['label'] }}</p>@endif
        <dl class="creds">
            @if (!empty($c['chat_id']))<div><dt>Secret Room Key</dt><dd><code>{{ $c['chat_id'] }}</code><button class="btn ghost sm" type="button" data-copy="{{ $c['chat_id'] }}">Copy</button></dd></div>@endif
            @if (!empty($c['member_1']))<div><dt>Pass Code 1</dt><dd><code>{{ $c['member_1'] }}</code><button class="btn ghost sm" type="button" data-copy="{{ $c['member_1'] }}">Copy</button></dd></div>@endif
            @if (!empty($c['member_2']))<div><dt>Pass Code 2</dt><dd><code>{{ $c['member_2'] }}</code><button class="btn ghost sm" type="button" data-copy="{{ $c['member_2'] }}">Copy</button></dd></div>@endif
        </dl>
        @php
            $msg = "Your private 1two1 chat room\n\n"
                .(!empty($c['chat_id']) ? "Secret Room Key: {$c['chat_id']}\n" : '')
                .(!empty($c['member_1']) ? "Pass Code 1: {$c['member_1']}\n" : '')
                .(!empty($c['member_2']) ? "Pass Code 2: {$c['member_2']}\n" : '')
                ."\nOpen ".url('/')." (a Private / Incognito window is best).\n"
                ."Each person uses their own Pass Code. Then the two of you agree a secret phrase between yourselves and never share it with anyone, including us.\n";
        @endphp
        <div class="actions">
            <button class="btn primary" type="button" data-copy="{{ $msg }}">Copy message for the customer</button>
            <button class="btn ghost" type="button" data-close>I have saved them</button>
        </div>
    </div>
</div>
@endif

<div class="head">
    <div>
        <h1>Rooms</h1>
        <p class="muted">Members open their own Temp or Long-term rooms on the website. Here you can watch usage and block or delete a room. Message content is end-to-end encrypted, so it is not visible here, by design.</p>
    </div>
    <form method="post" action="{{ route('operator.rooms.store') }}" class="newroom">
        @csrf
        <input type="text" name="label" maxlength="120" placeholder="Note for you, e.g. customer or order no." autocomplete="off">
        <select name="disappear_seconds" aria-label="Disappearing messages">
            <option value="0">Messages kept</option>
            <option value="30">Vanish after 30 s</option>
            <option value="300">Vanish after 5 min</option>
            <option value="3600">Vanish after 1 hour</option>
            <option value="86400">Vanish after 24 hours</option>
        </select>
        <input type="date" name="expires_at" aria-label="Room ends on" title="Optional: room closes on this date" min="{{ now()->addDay()->toDateString() }}">
        <button class="btn ghost" type="submit">New long-term room</button>
    </form>
</div>

<div class="stats">
    <div class="stat"><b>{{ $stats['total'] }}</b><span>Rooms open</span></div>
    <div class="stat"><b>{{ $stats['temp'] }}</b><span>Temp</span></div>
    <div class="stat"><b>{{ $stats['long'] }}</b><span>Long term</span></div>
    <div class="stat"><b>{{ $stats['today'] }}</b><span>Created today</span></div>
    <div class="stat"><b>{{ $stats['disabled'] }}</b><span>Blocked</span></div>
    <div class="stat live"><b>{{ $stats['live'] }}</b><span>Online now</span></div>
</div>
<p class="muted small"><a href="{{ route('operator.stats') }}">See daily, weekly, monthly and custom statistics &rarr;</a></p>

<div class="searchbar"><input type="search" id="room-filter" placeholder="Filter rooms by note or status" autocomplete="off" aria-label="Filter rooms"></div>

<div class="panel">
    @forelse ($rooms as $r)
        @php
            $m1 = $r->members->firstWhere('slot', 1);
            $m2 = $r->members->firstWhere('slot', 2);
        @endphp
        <article class="room" data-search="{{ strtolower(($r->label ?: 'room #'.$r->id).' '.($r->isExpired() ? 'expired' : $r->status)) }}">
            <div class="room-main">
                <div class="room-title">
                    <b>{{ $r->label ?: 'Room #'.$r->id }}</b>
                    <span @class(['pill', 'mode-temp' => $r->mode === 'temp', 'mode-long' => $r->mode !== 'temp'])>{{ $r->mode === 'temp' ? 'Temp' : 'Long term' }}</span>
                    @if ($r->isExpired())
                        <span class="pill off">expired</span>
                    @else
                        <span @class(['pill', 'ok' => $r->status === 'active', 'off' => $r->status !== 'active'])>{{ $r->status }}</span>
                    @endif
                </div>
                <div class="muted small">
                    Created {{ $r->created_at->diffForHumans() }} ·
                    @if ($r->last_activity_at) last activity {{ $r->last_activity_at->diffForHumans() }} @else no activity yet @endif
                    · {{ $r->source === 'self' ? 'opened by members' : 'opened by operator' }}
                    · auto-deletes {{ $r->dueAt()->diffForHumans() }} if nobody returns
                    @if ($r->disappear_seconds) · vanish {{ $r->disappear_seconds >= 3600 ? ($r->disappear_seconds / 3600).' h' : ($r->disappear_seconds >= 60 ? ($r->disappear_seconds / 60).' min' : $r->disappear_seconds.' s') }} @endif
                    @if ($r->expires_at) · ends {{ $r->expires_at->format('d M Y') }} @endif
                </div>
            </div>
            <div class="members">
                @foreach ([1 => $m1, 2 => $m2] as $slot => $m)
                    <div class="member" title="Member {{ $slot }}">
                        <i @class(['dot', 'on' => $m?->isOnline(), 'seen' => $m?->joined_at && ! $m->isOnline()])></i>
                        <span>M{{ $slot }}</span>
                        <small>{{ $m?->joined_at ? ($m->isOnline() ? 'online' : 'joined') : 'not joined' }}</small>
                    </div>
                @endforeach
            </div>
            <div class="room-actions">
                <form method="post" action="{{ route('operator.rooms.toggle', $r) }}" @if ($r->status === 'active') data-confirm="Block this room? Both members are signed out and nobody can enter until you unblock it." @endif>@csrf<button class="btn ghost sm" type="submit">{{ $r->status === 'active' ? 'Block' : 'Unblock' }}</button></form>
                <form method="post" action="{{ route('operator.rooms.kick', [$r, 'all']) }}" data-confirm="Sign both members out right now?">@csrf<button class="btn ghost sm" type="submit">Sign out both</button></form>
                <details class="more-actions">
                    <summary class="btn ghost sm">Manage</summary>
                    <div class="manage">
                        <form method="post" action="{{ route('operator.rooms.update', $r) }}" class="stack tight">
                            @csrf @method('PATCH')
                            <label>Note<input type="text" name="label" maxlength="120" value="{{ $r->label }}" autocomplete="off"></label>
                            <label>Disappearing messages
                                <select name="disappear_seconds">
                                    @foreach ([0 => 'Messages kept', 30 => '30 seconds', 300 => '5 minutes', 3600 => '1 hour', 86400 => '24 hours'] as $s => $l)
                                        <option value="{{ $s }}" @selected($r->disappear_seconds === $s)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Room ends on<input type="date" name="expires_at" value="{{ $r->expires_at?->toDateString() }}"></label>
                            <button class="btn primary sm" type="submit">Save changes</button>
                        </form>
                        <div class="manage-btns">
                            <form method="post" action="{{ route('operator.rooms.kick', [$r, 1]) }}" data-confirm="Sign member 1 out right now?">@csrf<button class="btn ghost sm" type="submit">Sign out M1</button></form>
                            <form method="post" action="{{ route('operator.rooms.kick', [$r, 2]) }}" data-confirm="Sign member 2 out right now?">@csrf<button class="btn ghost sm" type="submit">Sign out M2</button></form>
                            <form method="post" action="{{ route('operator.rooms.wipe', $r) }}" data-confirm="Erase all stored messages in this room? This cannot be undone.">@csrf<button class="btn warn sm" type="submit">Erase messages</button></form>
                            <form method="post" action="{{ route('operator.rooms.reissue', [$r, 1]) }}" data-confirm="Issue a new Pass Code for member 1? The old one stops working immediately.">@csrf<button class="btn ghost sm" type="submit">New code M1</button></form>
                            <form method="post" action="{{ route('operator.rooms.reissue', [$r, 2]) }}" data-confirm="Issue a new Pass Code for member 2? The old one stops working immediately.">@csrf<button class="btn ghost sm" type="submit">New code M2</button></form>
                            <form method="post" action="{{ route('operator.rooms.reset', $r) }}" data-confirm="Reset this room? New Secret Room Key and Pass Codes, all messages erased, secret phrase cleared. The old codes stop working.">@csrf<button class="btn warn sm" type="submit">Reset room (new codes)</button></form>
                            <form method="post" action="{{ route('operator.rooms.destroy', $r) }}" data-confirm="Delete this room completely? Its codes will stop working. This cannot be undone.">@csrf @method('DELETE')<button class="btn danger sm" type="submit">Delete room</button></form>
                        </div>
                    </div>
                </details>
            </div>
        </article>
    @empty
        <div class="emptyrooms">
            <h3>No rooms open right now</h3>
            <p class="muted">Rooms appear here as soon as people open them on the website. Temp rooms disappear 30 minutes after both members leave.</p>
        </div>
    @endforelse
</div>

<p class="muted small foot">Support address shown to members: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></p>
@endsection
