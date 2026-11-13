@extends('info.layout')
@section('title', 'FAQ')
@section('content')
<p class="eyebrow">FAQ</p>
<h1>Quick answers.</h1>

<div class="faq">
    <details id="random"><summary>What is Random connect, and is it safe?</summary><p>Open the <b>Random</b> tab, confirm you are 18 or older, and press <i>Look for someone</i>. When another person is looking at the same time, you are paired in a fresh Temp room, usually within seconds. You can pick a topic to be matched with someone who chose the same one, or choose Anyone. Either of you can end the chat at any moment from the room settings. Nobody is named, nothing is saved on your device, and messages are end-to-end encrypted: your two browsers agree the secret between themselves, so we never hold the key.</p><p>It is still a stranger. Never share your real name, address, photos, passwords or money. If someone makes you uncomfortable, press <b>Report and end chat</b>. We cannot read conversations, so a report ends the chat and is counted; if someone is in danger, contact your local emergency services.</p></details>
    <details><summary>What is the "safety code" in a random chat?</summary><p>Both of you see the same short code at the top of the chat. If you compare it (for example by typing it), you know nobody is secretly sitting between your two browsers. It is optional.</p></details>
    <details open><summary>Do I need an account or an app?</summary><p>No. Open the website in any modern browser, create a room and share the key. That is all.</p></details>
    <details><summary>What do I send to the other person?</summary><p>The Secret Room Key and <em>their</em> Pass Code. The site gives you a ready-made message to copy. Tell them the secret phrase separately.</p></details>
    <details><summary>What is the difference between the key, the Pass Code and the secret phrase?</summary><p>The key is the room's address, the Pass Code is each person's seat (so only two people can enter), and the secret phrase is the lock that encrypts what you write.</p></details>
    <details><summary>What does "Temp" mean exactly?</summary><p>When <b>both</b> of you are offline, a 30-minute clock starts. If either of you signs back in, it resets. If it runs out, the room and every message are deleted. One person being online keeps it alive.</p></details>
    <details><summary>And "Long term"?</summary><p>Same idea with a 30-day clock. Each visit resets it. After 30 days with nobody around, it is deleted.</p></details>
    <details><summary>My Temp room expired. Can you restore it?</summary><p>No. It is deleted completely, by design. Create a new room.</p></details>
    <details><summary>I lost the Secret Room Key or a Pass Code.</summary><p>We only keep one-way hashes, so we cannot show or resend them. Create a new room.</p></details>
    <details><summary>I forgot the secret phrase.</summary><p>It cannot be recovered by anyone. That is what keeps the chat private. Start a new room.</p></details>
    <details><summary>Can the website owner read my messages?</summary><p>No. Messages are encrypted in your browser before they are sent, and the key never leaves your device. See the <a href="/privacy">privacy statement</a>.</p></details>
    <details><summary>Can I be signed in on two devices?</summary><p>Each Pass Code allows one live session. Signing in somewhere else ends the first one, and you will see a notice. If that was not you, tell your partner.</p></details>
    <details><summary>Is Incognito mode necessary?</summary><p>It is not required, but recommended. A site cannot force it. We store nothing on your device either way.</p></details>
    <details><summary>Can more than two people join?</summary><p>No. A room has exactly two seats, which is what keeps it private.</p></details>
    <details><summary>What if someone guesses my code?</summary><p>After 8 wrong tries a room locks for 15 minutes, and even someone who got in would still need your secret phrase to read anything.</p></details>
</div>
@endsection
