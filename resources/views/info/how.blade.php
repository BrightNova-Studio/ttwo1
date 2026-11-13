@extends('info.layout')
@section('title', 'How it works')
@php
    $seoTitle = 'How 1two1 Works: A Private Chat Room in 4 Steps';
    $seoDesc = 'Create a private, end-to-end encrypted chat room, share the Secret Room Key and Pass Code, agree a secret phrase, and chat. No account or app needed.';
@endphp
@section('content')
<p class="eyebrow">How it works</p>
<h1>A locked room with exactly two keys.</h1>
<p class="lead">1two1 gives two people a private place to talk. There are no accounts to make and nothing to install. You can be chatting in under a minute.</p>

<ol class="steps">
    <li>
        <b>Create a room</b>
        <p>On the home page choose <a href="/temp-vs-long">Temp or Long term</a> and press <i>Create</i>. You instantly get three things: a <b>Secret Room Key</b> (like <code>KR-4821</code>) and two <b>Pass Codes</b> (like <code>482-913</code>), one for you and one for the other person.</p>
    </li>
    <li>
        <b>Send the key and their Pass Code</b>
        <p>Give your partner the Secret Room Key and <em>their</em> Pass Code. Tell them in person, or send it over a channel you trust. Keep your own Pass Code to yourself.</p>
    </li>
    <li>
        <b>Agree on a secret phrase</b>
        <p>The first person in chooses a <b>secret phrase</b> (10 or more characters) and tells the other person separately, ideally face to face. It scrambles every message on your device before it is sent. We never see it and can never recover it.</p>
    </li>
    <li>
        <b>Chat</b>
        <p>Messages, emoji, replies, reactions, view-once messages, disappearing messages and nicknames all work. Everything is encrypted on your device with <b>AES-256</b> before it leaves it.</p>
    </li>
    <li>
        <b>Leave, and it cleans itself up</b>
        <p>Close the tab and everything on your screen is wiped. When both of you have been away long enough, the room and all its messages are deleted for good: 30 minutes for Temp rooms, 30 days for Long-term rooms.</p>
    </li>
</ol>

<div class="grid2">
    <div class="box">
        <h3>What you need to remember</h3>
        <ul>
            <li><b>Secret Room Key</b>: the address of the room.</li>
            <li><b>Pass Code</b>: your personal seat. One per person, one live session at a time.</li>
            <li><b>Secret phrase</b>: the lock. Only the two of you know it.</li>
        </ul>
    </div>
    <div class="box">
        <h3>Good habits</h3>
        <ul>
            <li>Use an <b>Incognito / Private</b> window.</li>
            <li>Never share the secret phrase in the same message as the key.</li>
            <li>Close the tab when you are done. Press <kbd>Esc</kbd> twice to hide the screen instantly.</li>
        </ul>
    </div>
</div>

<div class="note">
    <b>Codes cannot be recovered.</b> We store them only as one-way hashes, so we cannot look them up or resend them. If a Temp room has expired, or a code is lost, simply create a new room.
</div>
@endsection
