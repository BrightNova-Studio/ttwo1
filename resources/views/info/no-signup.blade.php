@extends('info.layout')
@section('title', 'Chat without signup')
@php
    $seoTitle = 'Chat Without Sign Up: Free Anonymous Chat, No Account | 1two1';
    $seoDesc = 'Chat online with no sign up, no email and no phone number. 1two1 gives you a free private room for two in seconds, with no app to install.';
@endphp
@section('content')
<p class="eyebrow">No sign up</p>
<h1>Chat online without signing up.</h1>
<p class="lead">No account, no email address, no phone number, no password. With <b>1two1</b> you press Create, send one invite, and you are talking in a private room for two.</p>

<div class="grid2">
    <div class="box good">
        <h3>What you skip</h3>
        <ul>
            <li>No registration form or email verification.</li>
            <li>No phone number or contact list to hand over.</li>
            <li>No app to download (a free <a href="/android">Android app</a> is optional).</li>
        </ul>
    </div>
    <div class="box good">
        <h3>What you still get</h3>
        <ul>
            <li>End-to-end encryption in your browser.</li>
            <li>Rooms that delete themselves, plus optional <a href="/secret-chat">vanishing messages</a>.</li>
            <li>A <a href="/one-to-one-chat">one-to-one</a> room that nobody else can join.</li>
        </ul>
    </div>
</div>

<h2 class="h2">How to chat without an account</h2>
<ol class="steps">
    <li><b>Create a room</b><p>Open the <a href="/">home page</a> and press Create.</p></li>
    <li><b>Send the invite</b><p>Share the room key and the other person&rsquo;s Pass Code through any app.</p></li>
    <li><b>Chat</b><p>Both of you join and talk. When you leave, the room cleans itself up.</p></li>
</ol>
<p>Want to meet someone new instead? Try <a href="/anonymous-chat">anonymous random chat</a>. Learn more: <a href="/about-1two1">what is 1two1</a>, <a href="/privacy">privacy statement</a>.</p>
@endsection
