@extends('info.layout')
@section('title', 'FAQ')
@php
    $seoTitle = '1two1 FAQ: Private, Temp and Random Chat Answers';
    $seoDesc = 'Answers about 1two1 private chat rooms: accounts, secret phrases, Temp and Long-term rooms, Random connect, safety codes, and what we can and cannot see.';
    $faqs = [
        ['random', 'What is Random connect, and is it safe?', '<p>Open the <b>Random</b> tab, confirm you are 18 or older, and press <i>Look for someone</i>. When another person is looking at the same time, you are paired in a fresh Temp room, usually within seconds. You can pick a topic to be matched with someone who chose the same one, or choose Anyone. Either of you can end the chat at any moment from the room settings. Nobody is named, nothing is saved on your device, and messages are end-to-end encrypted: your two browsers agree the secret between themselves, so we never hold the key.</p><p>It is still a stranger. Never share your real name, address, photos, passwords or money. If someone makes you uncomfortable, press <b>Report and end chat</b>. We cannot read conversations, so a report ends the chat and is counted; if someone is in danger, contact your local emergency services.</p>'],
        [null, 'What is the "safety code" in a random chat?', '<p>Both of you see the same short code at the top of the chat. If you compare it (for example by typing it), you know nobody is secretly sitting between your two browsers. It is optional.</p>'],
        [null, 'Do I need an account or an app?', '<p>No. Open the website in any modern browser, create a room and share the key. That is all.</p>'],
        [null, 'What do I send to the other person?', '<p>The Secret Room Key and <em>their</em> Pass Code. The site gives you a ready-made message to copy. Tell them the secret phrase separately.</p>'],
        [null, 'What is the difference between the key, the Pass Code and the secret phrase?', '<p>The key is the room\'s address, the Pass Code is each person\'s seat (so only two people can enter), and the secret phrase is the lock that encrypts what you write.</p>'],
        [null, 'What does "Temp" mean exactly?', '<p>When <b>both</b> of you are offline, a 30-minute clock starts. If either of you signs back in, it resets. If it runs out, the room and every message are deleted. One person being online keeps it alive.</p>'],
        [null, 'And "Long term"?', '<p>Same idea with a 30-day clock. Each visit resets it. After 30 days with nobody around, it is deleted.</p>'],
        [null, 'My Temp room expired. Can you restore it?', '<p>No. It is deleted completely, by design. Create a new room.</p>'],
        [null, 'I lost the Secret Room Key or a Pass Code.', '<p>We only keep one-way hashes, so we cannot show or resend them. Create a new room.</p>'],
        [null, 'I forgot the secret phrase.', '<p>It cannot be recovered by anyone. That is what keeps the chat private. Start a new room.</p>'],
        [null, 'Can the website owner read my messages?', '<p>No. Messages are encrypted in your browser before they are sent, and the key never leaves your device. See the <a href="/privacy">privacy statement</a>.</p>'],
        [null, 'Can I be signed in on two devices?', '<p>Each Pass Code allows one live session. Signing in somewhere else ends the first one, and you will see a notice. If that was not you, tell your partner.</p>'],
        [null, 'Is Incognito mode necessary?', '<p>It is not required, but recommended. A site cannot force it. We store nothing on your device either way.</p>'],
        [null, 'Can more than two people join?', '<p>No. A room has exactly two seats, which is what keeps it private.</p>'],
        [null, 'What if someone guesses my code?', '<p>After 8 wrong tries a room locks for 15 minutes, and even someone who got in would still need your secret phrase to read anything.</p>'],
    ];
    $extraJson = [[
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($f) => [
            '@type' => 'Question',
            'name' => $f[1],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags(str_replace('</p>', ' ', $f[2])))],
        ], $faqs),
    ]];
@endphp
@section('content')
<p class="eyebrow">FAQ</p>
<h1>Quick answers.</h1>

<div class="faq">
    @foreach ($faqs as $i => $f)
        <details @if ($f[0]) id="{{ $f[0] }}" @endif @if ($i === 2) open @endif><summary>{{ $f[1] }}</summary>{!! $f[2] !!}</details>
    @endforeach
</div>
@endsection
