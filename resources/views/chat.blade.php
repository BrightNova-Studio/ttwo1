<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#06060a">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <title>1two1</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='9' fill='%2308080c'/%3E%3Ccircle cx='12.5' cy='16' r='7' fill='none' stroke='%238b7bff' stroke-width='2.4'/%3E%3Ccircle cx='19.5' cy='16' r='7' fill='none' stroke='%2346b0ff' stroke-width='2.4'/%3E%3C/svg%3E">
    @vite(['resources/js/chat/main.js'])
</head>
<body>
<div class="ambient" aria-hidden="true"><i class="orb o1"></i><i class="orb o2"></i><i class="orb o3"></i><i class="grain"></i></div>

<div id="tip" class="tip" hidden></div>

<main id="gate" class="gate" data-support="{{ $supportEmail }}">
    <section class="card" aria-live="polite">
        <div class="brand">
            <span class="mark" aria-hidden="true"><i></i><i></i></span>
            <span class="word">1two1</span>
        </div>

        <h1 id="gate-title">A private room for two.</h1>
        <p id="gate-sub" class="sub">End-to-end encrypted. No accounts, no cookies, nothing saved on this device.</p>

        <div id="gate-note" class="gate-note" hidden></div>

        <ol class="steps" aria-hidden="true"><li class="on"></li><li></li><li></li></ol>

        <form id="step-1" class="step on" autocomplete="off" novalidate>
            <label for="chatId">Chat ID</label>
            <div class="inp"><input id="chatId" name="c_{{ bin2hex(random_bytes(3)) }}" type="text" inputmode="text" autocomplete="off" autocapitalize="characters" autocorrect="off" spellcheck="false" placeholder="XXXX-XXXX-XXXX-XXXX" maxlength="19" required></div>
            <p class="err" role="alert"></p>
            <button class="btn primary block" type="submit"><span>Continue</span></button>
        </form>

        <form id="step-2" class="step" autocomplete="off" novalidate>
            <label for="memberId">Your Member ID</label>
            <div class="inp"><input id="memberId" class="mask" name="m_{{ bin2hex(random_bytes(3)) }}" type="text" autocomplete="off" autocapitalize="characters" autocorrect="off" spellcheck="false" placeholder="XXXX-XXXX-XXXX-XXXX" maxlength="19" required>
                <button class="eye" type="button" data-reveal="memberId" aria-label="Show or hide"></button></div>
            <p class="err" role="alert"></p>
            <div class="row2">
                <button class="btn ghost" type="button" data-back="1">Back</button>
                <button class="btn primary grow" type="submit"><span>Continue</span></button>
            </div>
        </form>

        <form id="step-3" class="step" autocomplete="off" novalidate>
            <label for="secret">Shared secret phrase</label>
            <div class="inp"><input id="secret" class="mask" name="s_{{ bin2hex(random_bytes(3)) }}" type="text" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" placeholder="Only the two of you know it" maxlength="120" required>
                <button class="eye" type="button" data-reveal="secret" aria-label="Show or hide"></button></div>
            <p id="secret-hint" class="hint">This phrase never leaves your device. It locks your messages so even the operator cannot read them. Agree on it with your partner privately, and use at least 10 characters the first time.</p>
            <p class="err" role="alert"></p>
            <div class="row2">
                <button class="btn ghost" type="button" data-back="2">Back</button>
                <button class="btn primary grow" type="submit"><span>Unlock room</span></button>
            </div>
        </form>

        <ul class="trust" aria-label="Privacy promises">
            <li><b>AES-256</b> end-to-end</li>
            <li><b>0</b> cookies</li>
            <li><b>Wipes</b> on tab close</li>
        </ul>
    </section>

    <p class="support">Need a Chat ID or lost a Member ID?
        <a id="support-link" href="#">Email support</a>
    </p>
</main>

<div id="app" class="app" hidden></div>
<div id="toasts" class="toasts" aria-live="polite"></div>
</body>
</html>
