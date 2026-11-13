@extends('info.layout')
@section('title', 'Temp vs Long term')
@php
    $seoTitle = 'Temp vs Long-Term Private Chat Rooms | 1two1';
    $seoDesc = 'Temp rooms delete 30 minutes after you both leave. Long-term rooms last 30 days. Compare the two private, encrypted chat room modes and pick one.';
@endphp
@section('content')
<p class="eyebrow">Two modes</p>
<h1>Temp for a quick, quiet talk. Long term for a room you keep.</h1>
<p class="lead">Both modes are private and end-to-end encrypted. The only difference is how long the room waits for you to come back before it deletes itself.</p>

<div class="grid2 modes">
    <article class="box temp">
        <span class="chip">Temp</span>
        <h2>Gone {{ rtrim(rtrim(number_format($temp, 1), '0'), '.') }} minutes after you both leave</h2>
        <ul>
            <li>You can be online while the other person is offline. That is fine.</li>
            <li>The moment <b>both</b> of you are offline, a {{ (int) $temp }}-minute clock starts.</li>
            <li>If either of you signs in again before it runs out, the clock resets.</li>
            <li>When it runs out the room, its codes and every message are <b>deleted completely</b>. Nothing can bring it back.</li>
        </ul>
        <p class="when"><b>Best for:</b> a one-off conversation, sharing something sensitive, a quick agreement you do not want to leave behind.</p>
    </article>
    <article class="box long">
        <span class="chip">Long term</span>
        <h2>Kept until {{ (int) $long }} days with nobody around</h2>
        <ul>
            <li>Same rules, but the clock is {{ (int) $long }} days instead of {{ (int) $temp }} minutes.</li>
            <li>Every time either of you signs in, the {{ (int) $long }} days start over.</li>
            <li>Your history stays available between visits, for as long as the room lives.</li>
            <li>If nobody has visited for {{ (int) $long }} days, it is deleted for good.</li>
        </ul>
        <p class="when"><b>Best for:</b> a couple, partners in a project, family, or anyone who talks on and off over weeks.</p>
    </article>
</div>

<h2 class="h2">Side by side</h2>
<div class="tablewrap">
<table>
    <thead><tr><th></th><th>Temp</th><th>Long term</th></tr></thead>
    <tbody>
        <tr><td>Auto-deletes after both leave</td><td>{{ (int) $temp }} minutes</td><td>{{ (int) $long }} days</td></tr>
        <tr><td>Clock resets when someone signs in</td><td>Yes</td><td>Yes</td></tr>
        <tr><td>One person offline is fine</td><td>Yes</td><td>Yes</td></tr>
        <tr><td>End-to-end encrypted</td><td>Yes</td><td>Yes</td></tr>
        <tr><td>Disappearing and view-once messages</td><td>Yes</td><td>Yes</td></tr>
        <tr><td>Who can read it</td><td>Only the two of you</td><td>Only the two of you</td></tr>
        <tr><td>Can be brought back after it expires</td><td>No</td><td>No</td></tr>
    </tbody>
</table>
</div>

<div class="note">
    <b>How to choose:</b> if you would be happy for the conversation to be gone by the time you finish a coffee, pick <b>Temp</b>. If you want to come back tomorrow, pick <b>Long term</b>. You can always create another room.
</div>
@endsection
