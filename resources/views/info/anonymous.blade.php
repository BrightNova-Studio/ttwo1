@extends('info.layout')
@section('title', 'Anonymous chat')
@php
    $seoTitle = 'Anonymous Chat With Strangers, Encrypted & Safe | 1two1';
    $seoDesc = 'Meet someone new with Random connect: anonymous, encrypted one-to-one chat with no signup. Pick a topic, get paired, and leave or report at any time. 18+ only.';
@endphp
@section('content')
<p class="eyebrow">Anonymous chat</p>
<h1>Anonymous chat with someone new, without giving anything up.</h1>
<p class="lead">Random connect pairs you with another person who is looking at the same moment. No profile, no name, no photo. You are dropped into a private room that ends when either of you leaves. For adults (18+).</p>

<h2 class="h2">How Random connect works</h2>
<ol class="steps">
    <li><b>Open the Random tab</b><p>Confirm you are 18 or older and, if you like, choose a topic: just chatting, advice, games, music, movies, tech or books. Or choose Anyone.</p></li>
    <li><b>Get matched</b><p>You are paired with the person who has waited longest, preferring someone who picked the same topic. It usually takes seconds when the site is busy.</p></li>
    <li><b>Talk, then leave any time</b><p>Either of you can end the chat for both with one tap, or report it. When it ends, every message is erased.</p></li>
</ol>

<div class="grid2">
    <div class="box good">
        <h3>What keeps it anonymous and private</h3>
        <ul>
            <li>No account, no name, no profile picture of yours is ever uploaded.</li>
            <li>The chat is end-to-end encrypted. Your two browsers agree the secret between themselves, so the site never holds the key.</li>
            <li>A matching <b>safety code</b> on both screens shows that nobody is in between.</li>
            <li>The room is a Temp room: it is deleted soon after you both leave.</li>
        </ul>
    </div>
    <div class="box">
        <h3>Stay safe with strangers</h3>
        <ul>
            <li>Never share your real name, address, photos, passwords or money.</li>
            <li>You can leave, end the chat or <b>Report and end chat</b> at any moment.</li>
            <li>We cannot read conversations, so a report ends the chat and is counted. In an emergency, contact local emergency services.</li>
        </ul>
    </div>
</div>

<p>Prefer to talk to someone you already know? Create a <a href="/private-chat-room">private chat room</a> for two instead. Questions about safety are answered in the <a href="/faq#random">FAQ</a>, and the <a href="/privacy">privacy statement</a> explains exactly what is stored while you wait for a match.</p>
@endsection
