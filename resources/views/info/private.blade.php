@extends('info.layout')
@section('title', 'Private chat room')
@php
    $seoTitle = 'Private Chat Room for Two: Encrypted, No Signup | 1two1';
    $seoDesc = 'Start a private chat room for two people in seconds. End-to-end encrypted in your browser, no account, no app, no tracking. Only the two of you can read it.';
@endphp
@section('content')
<p class="eyebrow">Private chat room</p>
<h1>A private chat room for two, ready in seconds.</h1>
<p class="lead">Need to talk to one person without a platform watching? 1two1 opens a locked room with exactly two seats. No sign-up, no app to install, and your messages are scrambled on your device before they are sent.</p>

<div class="grid2">
    <div class="box good">
        <h3>Why people use it</h3>
        <ul>
            <li>Share something sensitive without leaving it in a chat history.</li>
            <li>Talk to a client, a patient, a source or a partner without handing over a phone number.</li>
            <li>Agree something quickly, then let the room delete itself.</li>
        </ul>
    </div>
    <div class="box">
        <h3>What makes it private</h3>
        <ul>
            <li><b>End-to-end encryption.</b> AES-256, with a key made in your browser from a secret phrase only you two know.</li>
            <li><b>No accounts.</b> We never learn who you are.</li>
            <li><b>No cookies or trackers.</b> Close the tab and nothing is left on the device.</li>
        </ul>
    </div>
</div>

<h2 class="h2">How to start a private chat room</h2>
<ol class="steps">
    <li><b>Create the room</b><p>On the <a href="/">home page</a> press <i>Create</i>, choose <a href="/temp-vs-long">Temp or Long term</a>, and you get a Secret Room Key and two Pass Codes.</p></li>
    <li><b>Send the key and their Pass Code</b><p>Give the other person the key and <em>their</em> Pass Code. Share the secret phrase separately, ideally face to face.</p></li>
    <li><b>Chat</b><p>Messages, emoji, replies, reactions, view-once and disappearing messages all work, and nobody else can read them, including us.</p></li>
</ol>

<h2 class="h2">Exactly two people, by design</h2>
<p>A room has two seats and no more. Each person has their own Pass Code and one live session at a time, so a third person cannot slip in. Several wrong guesses lock the room for 15 minutes, and even someone who did get in would still need your secret phrase to read anything.</p>
<p>Want a room that disappears on its own? See the <a href="/temporary-chat-room">temporary chat room</a>. To read exactly what we can and cannot see, read the <a href="/privacy">privacy statement</a>.</p>
@endsection
