@extends('info.layout')
@section('title', 'What is 1two1?')
@php
    $seoTitle = 'What is 1two1 (121)? Free Secret Chat Rooms for Two';
    $seoDesc = '1two1, also written 121 or "one two one", is a free private chat for exactly two people. End-to-end encrypted, no signup, no app. Learn what it is and how to use it.';
    $faqs = [
        ['What is 1two1?', '1two1 is a free website for private, one-to-one chat. You create a room with two seats, send the other person their code, and talk. Messages are encrypted in your browser, and the room deletes itself after you leave.'],
        ['Is 1two1 the same as 121?', 'Yes. People write the name as 1two1, 121, 1-2-1 or "one two one". They all mean this site. "121" is also a common way to say "one to one", which is exactly what 1two1 is: a chat between two people.'],
        ['Do I need an account or an app?', 'No. 1two1 works in any modern browser, with no sign-up. There is also a free Android app, which is the same site with an app lock.'],
        ['Is 1two1 free?', 'Yes, it is free to use.'],
        ['Who can read my messages?', 'Only the two people in the room. Messages are encrypted on your device, and with a secret phrase not even the site could read them.'],
    ];
    $extraJson = [[
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs),
    ]];
@endphp
@section('content')
<p class="eyebrow">About</p>
<h1>What is 1two1 (121)?</h1>
<p class="lead"><b>1two1</b> (also written <b>121</b>, <b>1-2-1</b> or &ldquo;one two one&rdquo;) is a free secret chat for exactly two people. You open a room, share a key with the one person you want to talk to, and chat. No account, no phone number, no app to install.</p>

<div class="grid2">
    <div class="box good">
        <h3>What you get</h3>
        <ul>
            <li>A <a href="/private-chat-room">private chat room</a> with exactly two seats.</li>
            <li>End-to-end encryption (AES-256) done in your browser.</li>
            <li>Temp rooms that delete themselves, or Long-term rooms kept for up to 30 days.</li>
            <li>Vanish mode, view-once messages, replies and reactions.</li>
            <li><a href="/anonymous-chat">Random connect</a>: meet someone new, anonymously (18+).</li>
        </ul>
    </div>
    <div class="box">
        <h3>What you do not need</h3>
        <ul>
            <li>No sign-up, no email address, no password.</li>
            <li>No tracking cookies. Nothing is saved on your device.</li>
            <li>No download, although a free <a href="/android">Android app</a> is available.</li>
        </ul>
    </div>
</div>

<h2 class="h2">Why &ldquo;121&rdquo;?</h2>
<p>&ldquo;121&rdquo; is a common way of writing &ldquo;one to one&rdquo;, a conversation between two people and nobody else. That is what 1two1 is built for. If you searched for <b>121 chat</b>, <b>1 to 1 chat</b> or <b>one to one chat</b>, read about our <a href="/one-to-one-chat">one-to-one chat rooms</a>.</p>

<h2 class="h2">How to start</h2>
<ol class="steps">
    <li><b>Create a room</b><p>On the <a href="/">home page</a>, choose Temp or Long term and press Create. You get a Secret Room Key and a Pass Code for each of you.</p></li>
    <li><b>Send the invite</b><p>Copy the ready-made invite and send it to the other person through any app. Optionally agree a secret phrase for extra privacy.</p></li>
    <li><b>Talk</b><p>Both of you open the site, join with your codes, and chat. When you both leave, the room is deleted on schedule.</p></li>
</ol>

<h2 class="h2">Common questions</h2>
<dl class="defs">
    @foreach ($faqs as $f)
        <dt>{{ $f[0] }}</dt>
        <dd>{{ $f[1] }}</dd>
    @endforeach
</dl>
<p>More answers are in the <a href="/faq">FAQ</a>, and the <a href="/privacy">privacy statement</a> lists exactly what is and is not stored.</p>
@endsection
