@extends('info.layout')
@section('title', 'Secret chat')
@php
    $seoTitle = 'Secret Chat Online: Private Encrypted Rooms, No Signup | 1two1';
    $seoDesc = 'Talk in secret with one person. 1two1 gives you an encrypted secret chat room in seconds: no account, no app, and messages that can vanish after they are read.';
@endphp
@section('content')
<p class="eyebrow">Secret chat</p>
<h1>Secret chat online, with exactly one other person.</h1>
<p class="lead">Sometimes a conversation is just for two. 1two1 is a secret chat you can open in seconds: a locked room with two seats, encryption in your browser, and messages that can erase themselves. Nobody needs an account, and nothing is left on your device.</p>

<h2 class="h2">What makes a chat secret</h2>
<div class="grid2">
    <div class="box good">
        <h3>Locked to two people</h3>
        <ul>
            <li>A room has two seats and each seat has its own code, so a third person cannot slip in.</li>
            <li>Wrong guesses lock the room for 15 minutes.</li>
            <li>Add an optional <b>secret phrase</b> that only you two know, so even the site cannot read the messages.</li>
        </ul>
    </div>
    <div class="box good">
        <h3>Messages that disappear</h3>
        <ul>
            <li><b>Vanish mode</b> erases each message 30 seconds after the other person has seen it.</li>
            <li><b>View once</b> messages can be opened only one time.</li>
            <li>Choose disappearing messages from 30 seconds up to 24 hours, or <b>Clear chat</b> for both.</li>
            <li><a href="/temporary-chat-room">Temp rooms</a> delete themselves 30 minutes after you both leave.</li>
        </ul>
    </div>
</div>

<h2 class="h2">Start a secret chat in three steps</h2>
<ol class="steps">
    <li><b>Create a room</b><p>Open the <a href="/">home page</a> and choose Create. Pick Temp for a quick talk, or Long term to keep the room for up to 30 days.</p></li>
    <li><b>Share the invite, not the secret</b><p>Send the key and the other person&rsquo;s Pass Code. If you set a secret phrase, tell it to them a different way, ideally face to face.</p></li>
    <li><b>Chat, then let it vanish</b><p>Switch on Vanish mode if you want, and leave when you are done. The room cleans itself up.</p></li>
</ol>

<h2 class="h2">Tips for keeping a chat secret</h2>
<ul>
    <li>Use a Private or Incognito window, and press <b>Hide now</b> if someone walks up behind you.</li>
    <li>Never send your own Pass Code or the secret phrase in the same message as the room key.</li>
    <li>Remember that the other person can still take a screenshot or write down what you say.</li>
</ul>

<p>Want to see how the encryption works? Read <a href="/how-it-works">how 1two1 works</a>, or what we store in the <a href="/privacy">privacy statement</a>. Looking for a chat with someone new? Try <a href="/anonymous-chat">anonymous chat</a>.</p>
@endsection
