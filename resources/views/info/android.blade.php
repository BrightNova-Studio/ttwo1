@extends('info.layout')
@section('title', 'Android app')
@php
    $seoTitle = '1two1 Android App: Download the Private Chat APK';
    $seoDesc = 'Download the 1two1 Android app: encrypted private chat for two, with screenshot blocking and app lock. Signed APK with a published SHA-256 so you can verify it.';
    $extraJson = $android ? [[
        '@context' => 'https://schema.org',
        '@type' => 'MobileApplication',
        'name' => '1two1',
        'operatingSystem' => 'ANDROID',
        'applicationCategory' => 'CommunicationApplication',
        'softwareVersion' => $android['versionName'],
        'fileSize' => number_format($android['size'] / 1048576, 1).'MB',
        'downloadUrl' => rtrim(config('app.url'), '/').'/downloads/'.$android['file'],
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
    ]] : [];
@endphp
@section('content')
<p class="eyebrow">Android app</p>
<h1>1two1 for Android.</h1>
<p class="lead">The same private rooms, in an app that adds protection the browser cannot: no screenshots, and a fingerprint or PIN lock when you come back to it.</p>

@if ($android)
<div class="box good appbox">
    <div class="appbox-top">
        <div>
            <h2>Version {{ $android['versionName'] }}</h2>
            <p>Released {{ $android['released'] }} &middot; {{ number_format($android['size'] / 1048576, 2) }} MB &middot; needs Android {{ $android['minAndroid'] }} or newer</p>
        </div>
        <a class="cta big" href="/downloads/{{ $android['file'] }}" download="1two1-{{ $android['versionName'] }}.apk" rel="nofollow">Download APK</a>
    </div>
</div>

<h2 class="h2">Install in three steps</h2>
<ol class="steps">
    <li><b>Download the APK</b><p>Tap <i>Download APK</i> on your phone. Your browser may warn that this type of file can be harmful. That is Android's standard message for any app that does not come from the Play Store.</p></li>
    <li><b>Allow the install</b><p>Open the file. Android asks to let your browser install apps: switch that on for this one install. You can switch it off again afterwards.</p></li>
    <li><b>Open 1two1</b><p>Tap Install, then Open. The app asks for no permissions except the network and your lock screen.</p></li>
</ol>

<h2 class="h2">Check that the file is genuine</h2>
<p>The app is signed with our private key. Compare these values with your downloaded file before you install it. If they differ, do not install.</p>
<dl class="kv">
    <dt>APK SHA-256</dt><dd><code class="block">{{ $android['sha256'] }}</code></dd>
    <dt>Signing certificate SHA-256</dt><dd><code class="block">{{ strtoupper(implode(':', str_split($android['signingCertSha256'], 2))) }}</code></dd>
    <dt>Package name</dt><dd><code class="block">{{ $android['package'] }}</code></dd>
</dl>
<p class="small">To compute the checksum: on Windows run <code>certutil -hashfile 1two1.apk SHA256</code>; on Mac or Linux run <code>shasum -a 256 1two1.apk</code>. Android also shows the signer if you open the app's details after installing.</p>

<h2 class="h2">What the app adds</h2>
<div class="grid2">
    <div class="box good">
        <h3>Protection</h3>
        <ul>
            <li><b>No screenshots or screen recording</b>, and the chat is blanked in the recent-apps view.</li>
            <li><b>App lock:</b> after a minute away it asks for your fingerprint, face or PIN.</li>
            <li><b>No backups:</b> nothing from the app is copied to the cloud or to another phone.</li>
            <li><b>Secure connections only:</b> plain web traffic is refused, and it only trusts the phone's own certificates.</li>
        </ul>
    </div>
    <div class="box">
        <h3>Good to know</h3>
        <ul>
            <li>It is the same end-to-end encryption as the website. Messages are locked on your phone.</li>
            <li>The app opens only {{ parse_url(config('app.url'), PHP_URL_HOST) }}; other links open in your browser.</li>
            <li>A Google Play listing is planned. Until then, install from this page only.</li>
        </ul>
    </div>
</div>

@if (count($history))
<h2 class="h2">Version history</h2>
<div class="tablewrap">
<table>
    <thead><tr><th>Version</th><th>Date</th><th>What changed</th></tr></thead>
    <tbody>
        @foreach ($history as $r)
            <tr><td>{{ $r['version'] }}</td><td>{{ $r['date'] }}</td><td>{{ $r['notes'] }}</td></tr>
        @endforeach
    </tbody>
</table>
</div>
@endif
@else
<div class="note"><b>The Android app is not published yet.</b> Please check back soon. You can use 1two1 in any browser meanwhile.</div>
@endif
@endsection
