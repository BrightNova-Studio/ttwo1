@extends('info.layout')
@section('title', 'Encrypted chat')
@php
    $seoTitle = 'Encrypted Chat Online: End-to-End Private Messages for Two | 1two1';
    $seoDesc = 'Free end-to-end encrypted chat in your browser. 1two1 uses AES-256 encryption done on your device, with an optional secret phrase, no sign up and no app.';
@endphp
@section('content')
<p class="eyebrow">Encrypted chat</p>
<h1>End-to-end encrypted chat in your browser.</h1>
<p class="lead">In <b>1two1</b>, messages are encrypted on your device with AES-256 before they leave it, so the server only ever stores scrambled text. No account and no app are needed.</p>

<div class="grid2">
    <div class="box good">
        <h3>How it is protected</h3>
        <ul>
            <li>AES-256-GCM encryption runs in your browser.</li>
            <li>An optional <b>secret phrase</b> that only you two know makes the key impossible for the site to derive.</li>
            <li>Rooms are locked for 15 minutes after wrong guesses.</li>
        </ul>
    </div>
    <div class="box">
        <h3>Be realistic</h3>
        <ul>
            <li>The other person can still screenshot or copy what you write.</li>
            <li>Keep the secret phrase out of the message that carries the room key.</li>
        </ul>
    </div>
</div>
<p>See the details in <a href="/how-it-works">how 1two1 works</a> and the <a href="/privacy">privacy statement</a>. Start a <a href="/secret-chat">secret chat</a> or a <a href="/private-chat-room">private chat room</a> from the <a href="/">home page</a>.</p>
@endsection
