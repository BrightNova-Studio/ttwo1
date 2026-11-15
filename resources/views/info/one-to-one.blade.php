@extends('info.layout')
@section('title', 'One-to-one chat (121)')
@php
    $seoTitle = '121 Chat: Private One-to-One Chat Rooms, No Signup | 1two1';
    $seoDesc = 'A free 121 (one-to-one) chat for two people. 1two1 opens an encrypted private room in seconds: no account, no app, and the room deletes itself when you are done.';
@endphp
@section('content')
<p class="eyebrow">121 &middot; one-to-one chat</p>
<h1>121 chat: a private one-to-one room for two people.</h1>
<p class="lead">A 121 chat (&ldquo;one to one&rdquo;, also written 1-2-1 or 1 to 1) is a conversation between two people and nobody else. <b>1two1</b> is built for exactly that: every room has two seats, messages are encrypted in your browser, and there is nothing to sign up for.</p>

<div class="grid2">
    <div class="box good">
        <h3>Why a 1-to-1 room, not a group</h3>
        <ul>
            <li>Only two seats: nobody else can be added, invited or forwarded in.</li>
            <li>No phone numbers, no profile, no contact list to share.</li>
            <li>Each person has their own code, so you always know there are just two of you.</li>
        </ul>
    </div>
    <div class="box">
        <h3>Good for</h3>
        <ul>
            <li>Private conversations with a friend, partner or family member.</li>
            <li>One-to-one calls to arrange, interviews or support, without sharing personal contact details.</li>
            <li>Anything you would rather not leave in a chat history. Use <a href="/temporary-chat-room">Temp rooms</a> that delete themselves.</li>
        </ul>
    </div>
</div>

<h2 class="h2">How a 121 chat works on 1two1</h2>
<ol class="steps">
    <li><b>Create a room</b><p>On the <a href="/">home page</a>, press Create. You receive a Secret Room Key and one Pass Code for each of you.</p></li>
    <li><b>Send the invite</b><p>Give the other person the key and their Pass Code. That is all they need to join.</p></li>
    <li><b>Chat one to one</b><p>Send messages, replies, reactions and view-once messages. When you are both gone, the room is deleted.</p></li>
</ol>

<h2 class="h2">Meet someone new, one to one</h2>
<p>If you do not know the other person yet, <a href="/anonymous-chat">Random connect</a> pairs you with one stranger at a time, anonymously (for adults, 18+). Either of you can leave or report at any moment.</p>

<p>Also read: <a href="/about-1two1">what 1two1 is</a>, <a href="/secret-chat">secret chat</a>, and <a href="/private-chat-room">private chat rooms</a>.</p>
@endsection
