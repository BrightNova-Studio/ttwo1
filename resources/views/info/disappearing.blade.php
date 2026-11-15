@extends('info.layout')
@section('title', 'Disappearing messages')
@php
    $seoTitle = 'Disappearing Messages Chat: Self-Destructing, View Once | 1two1';
    $seoDesc = 'Free chat with disappearing messages. 1two1 offers Vanish mode, view-once messages, timed deletion and temporary rooms that delete themselves. No sign up.';
@endphp
@section('content')
<p class="eyebrow">Disappearing messages</p>
<h1>Chat with self-destructing messages.</h1>
<p class="lead">Leave no trail. <b>1two1</b> can erase messages after they are read, on a timer, or when the room ends, with no account needed.</p>

<div class="grid2">
    <div class="box good">
        <h3>Ways messages disappear</h3>
        <ul>
            <li><b>Vanish mode</b>: each message is erased 30 seconds after the other person has seen it.</li>
            <li><b>View once</b>: a message can be opened a single time.</li>
            <li><b>Timed deletion</b>: from 30 seconds up to 24 hours.</li>
            <li><b>Clear chat</b>: wipe everything for both people.</li>
        </ul>
    </div>
    <div class="box good">
        <h3>Rooms that disappear too</h3>
        <ul>
            <li><a href="/temporary-chat-room">Temp rooms</a> delete themselves after you both leave.</li>
            <li>Long-term rooms are removed after 30 days.</li>
        </ul>
    </div>
</div>
<p>Read about <a href="/secret-chat">secret chat</a>, <a href="/encrypted-chat">encrypted chat</a> and <a href="/chat-without-signup">chat without signup</a>, or <a href="/">create a room now</a>.</p>
@endsection
