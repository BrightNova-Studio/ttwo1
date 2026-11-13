@extends('info.layout')
@section('title', 'Temporary chat room')
@php
    $seoTitle = 'Temporary Chat Room That Deletes Itself | 1two1';
    $seoDesc = 'A temporary, encrypted chat room for two. When you both leave, a 30-minute clock starts, and the room and every message are deleted for good. No signup.';
@endphp
@section('content')
<p class="eyebrow">Temporary chat room</p>
<h1>A temporary chat room that deletes itself.</h1>
<p class="lead">Some conversations should not outlive the moment. A 1two1 Temp room lives only while one of you is around. When you have both gone, a {{ (int) $temp }}-minute clock starts, and then the room, its codes and every message are erased for good.</p>

<h2 class="h2">How the {{ (int) $temp }}-minute rule works</h2>
<ol class="steps">
    <li><b>Either of you can be online</b><p>One person leaving does not end the room. It stays open as long as at least one of you is signed in.</p></li>
    <li><b>The clock starts when you are both away</b><p>The moment both of you are offline, {{ (int) $temp }} minutes start counting down.</p></li>
    <li><b>Coming back resets it</b><p>If either of you signs in again before it runs out, the clock starts over.</p></li>
    <li><b>Then it is gone</b><p>When the time is up the room is deleted from the database, including every message. It cannot be restored, even by us.</p></li>
</ol>

<div class="grid2">
    <div class="box good">
        <h3>Extra ways to leave less behind</h3>
        <ul>
            <li><b>Disappearing messages:</b> vanish after 30 seconds, 5 minutes, 1 hour or 24 hours.</li>
            <li><b>View-once messages:</b> open one time, then erased.</li>
            <li><b>Clear chat:</b> erase everything for both of you with one tap.</li>
            <li><b>Hide now:</b> press Esc twice to hide the screen instantly.</li>
        </ul>
    </div>
    <div class="box">
        <h3>Need it to last?</h3>
        <p>Pick <b>Long term</b> instead. It uses the same privacy, with a {{ (int) $long }}-day clock. Compare them on the <a href="/temp-vs-long">Temp vs Long term</a> page.</p>
    </div>
</div>

<p>Temp rooms are end-to-end encrypted like every 1two1 room, so the content is unreadable to everyone except the two of you while the room exists. Learn the full picture on the <a href="/how-it-works">how it works</a> page, or start a <a href="/private-chat-room">private chat room</a> now.</p>
@endsection
