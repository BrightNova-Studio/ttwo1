@extends('info.layout')
@section('title', 'Privacy statement')
@section('content')
<p class="eyebrow">Privacy statement</p>
<h1>We built it so we cannot read your chats.</h1>
<p class="lead">This page says plainly what 1two1 does and does not do with your information, including its limits.</p>

<div class="grid2">
    <div class="box good">
        <h3>What we cannot see</h3>
        <ul>
            <li><b>Your messages.</b> They are encrypted on your device with a key made from your secret phrase. The key never leaves your device.</li>
            <li><b>Nicknames and reactions.</b> Encrypted the same way.</li>
            <li><b>Your secret phrase.</b> It is never sent to us and cannot be recovered.</li>
            <li><b>Who you are.</b> There are no accounts, names, phone numbers or emails.</li>
        </ul>
    </div>
    <div class="box">
        <h3>What the server holds</h3>
        <ul>
            <li>Scrambled (encrypted) messages, only until they are deleted.</li>
            <li>Your Secret Room Key and Pass Codes as <b>one-way hashes</b>, never in readable form.</li>
            <li>When a room was last active, which is how it knows when to expire.</li>
            <li>Anonymous counters (rooms created, expired, deleted) with no link to any room.</li>
        </ul>
    </div>
</div>

<h2 class="h2">The details</h2>
<dl class="defs">
    <dt>No cookies, no tracking</dt>
    <dd>Members get no cookie, no local storage and no analytics. There are no third-party requests: fonts and emoji are served from this site. Your sign-in lives only in the page's memory, so closing or reloading the tab signs you out.</dd>

    <dt>Deleted means deleted</dt>
    <dd>When a room expires, or is deleted, the room, both members and every message are removed from the database. A message you "unsend" or a view-once message you have opened has its content erased. "Clear chat" erases the stored messages for both of you.</dd>

    <dt>The operator cannot read chats</dt>
    <dd>The operator console shows usage numbers and can block or delete a room. It has no screen, route or database field that returns message text. It cannot see Secret Room Keys or Pass Codes either, because only hashes are stored.</dd>

    <dt>What we can still observe</dt>
    <dd>Like any website, the server must know a request arrived: the time, the size of a message and that a room is active. Your IP address is used briefly to limit abuse (for example, how many rooms one person can create per hour). Hosting providers may keep standard server access logs for a short period.</dd>

    <dt>Protection against guessing</dt>
    <dd>Codes are short so people can remember them. To keep them safe, 8 wrong tries on a room lock it for 15 minutes, each device is rate-limited, and your secret phrase, not the codes, is what actually encrypts your messages.</dd>

    <dt>Limits no website can remove</dt>
    <dd>Someone looking at your screen, a screenshot, malware on a device, or a partner who shares your codes and phrase can all expose a chat. Tell the other person to treat the room as carefully as you do. We recommend a Private / Incognito window.</dd>
</dl>

<div class="note">
    <b>Questions or a concern?</b> Write to <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>. We can delete or block a room, but we can never recover a lost code, a lost secret phrase, or a deleted conversation.
</div>
@endsection
