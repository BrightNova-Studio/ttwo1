<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#06060a">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <meta name="description" content="Open a secret, end-to-end encrypted room for exactly two people. Temp rooms vanish 30 minutes after you both leave.">
    <title>1two1 · Secret rooms for two</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @vite(['resources/js/chat/main.js'])
</head>
<body>
<div class="ambient" aria-hidden="true"><i class="orb o1"></i><i class="orb o2"></i><i class="orb o3"></i><i class="grain"></i></div>

<main id="gate" class="gate" data-support="{{ $supportEmail }}" data-temp="{{ $modes['temp'] }}" data-long="{{ $modes['long'] }}">
    <div id="tip" class="tip" hidden></div>

    <section class="card" aria-live="polite">
        <div class="brand">@include('partials.brand', ['u' => 'c'])</div>

        <h1 id="gate-title">A secret room for two.</h1>
        <p id="gate-sub" class="sub">End-to-end encrypted. No accounts, no cookies, nothing saved on this device.</p>

        <div id="gate-note" class="gate-note" hidden></div>

        <div id="tabs" class="tabs" role="tablist">
            <button id="tab-join" class="tab on" type="button" role="tab" aria-selected="true">Join a room</button>
            <button id="tab-new" class="tab" type="button" role="tab" aria-selected="false">Create a room</button>
        </div>

        {{-- ---------------------------------------------------------- join --}}
        <div id="pane-join">
            <ol class="steps" aria-hidden="true"><li class="on"></li><li></li><li></li></ol>

            <form id="step-1" class="step on" autocomplete="off" novalidate>
                <label for="chatId">Secret Room Key</label>
                <div class="inp"><input id="chatId" name="c_{{ bin2hex(random_bytes(3)) }}" type="text" inputmode="text" autocomplete="off" autocapitalize="characters" autocorrect="off" spellcheck="false" placeholder="AB-1234" maxlength="7" required></div>
                <p class="err" role="alert"></p>
                <button class="btn primary block" type="submit"><span>Continue</span></button>
            </form>

            <form id="step-2" class="step" autocomplete="off" novalidate>
                <label for="memberId">Your Pass Code</label>
                <div class="inp"><input id="memberId" class="mask" name="m_{{ bin2hex(random_bytes(3)) }}" type="text" inputmode="numeric" autocomplete="off" autocorrect="off" spellcheck="false" placeholder="123-456" maxlength="7" required>
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
                <p id="secret-hint" class="hint">This phrase never leaves your device. It locks your messages so nobody else, not even us, can read them. Agree on it with your partner privately, and use at least 10 characters the first time.</p>
                <p class="err" role="alert"></p>
                <div class="row2">
                    <button class="btn ghost" type="button" data-back="2">Back</button>
                    <button class="btn primary grow" type="submit"><span>Unlock room</span></button>
                </div>
            </form>
        </div>

        {{-- ---------------------------------------------------------- create --}}
        <div id="pane-new" hidden>
            <form id="new-form" novalidate>
                <label>Choose how long it lives</label>
                <div class="modes2" role="radiogroup" aria-label="Room type">
                    <button type="button" class="mode on" data-mode="temp" role="radio" aria-checked="true">
                        <span class="mode-ico" data-ico="clock"></span>
                        <b>Temp</b>
                        <small>Gone {{ (int) ($modes['temp'] / 60) }} minutes after you both leave</small>
                    </button>
                    <button type="button" class="mode" data-mode="long" role="radio" aria-checked="false">
                        <span class="mode-ico" data-ico="shield"></span>
                        <b>Long term</b>
                        <small>Kept until {{ (int) ($modes['long'] / 86400) }} days with nobody around</small>
                    </button>
                </div>
                <p id="mode-desc" class="hint"></p>
                <p class="err" role="alert"></p>
                <button class="btn primary block" type="submit"><span>Create my room</span></button>
                <p class="learnmore"><a href="/temp-vs-long" target="_blank" rel="noopener">Temp or Long term? See the difference</a></p>
            </form>
        </div>

        {{-- ------------------------------------------------- room created --}}
        <div id="pane-made" hidden>
            <div class="made-top"><span id="made-chip" class="chip">Temp</span><span id="made-line" class="hint"></span></div>
            <dl class="creds2">
                <div><dt>Secret Room Key</dt><dd><code id="mk-key"></code><button class="copy" type="button" data-copy-from="mk-key">Copy</button></dd></div>
                <div><dt>Your Pass Code</dt><dd><code id="mk-you"></code><button class="copy" type="button" data-copy-from="mk-you">Copy</button></dd></div>
                <div class="partner"><dt>Your partner's Pass Code</dt><dd><code id="mk-partner"></code><button class="copy" type="button" data-copy-from="mk-partner">Copy</button></dd></div>
            </dl>
            <p class="made-warn"><b>Shown only once.</b> We keep these only as one-way hashes and cannot show them again. Send your partner the key and <em>their</em> Pass Code now.</p>
            <button id="copy-invite" class="btn ghost block" type="button"><span>Copy invite for your partner</span></button>
            <button id="enter-made" class="btn primary block" type="button"><span>Enter my room</span></button>
        </div>

        <ul class="trust" aria-label="Privacy promises">
            <li><b>AES-256</b> end-to-end</li>
            <li><b>0</b> cookies</li>
            <li><b>Auto</b> deletes</li>
        </ul>
    </section>

    <nav class="learn" aria-label="Learn more">
        <a href="/how-it-works" target="_blank" rel="noopener">How it works</a>
        <a href="/temp-vs-long" target="_blank" rel="noopener">Temp vs Long</a>
        <a href="/privacy" target="_blank" rel="noopener">Privacy</a>
        <a href="/faq" target="_blank" rel="noopener">FAQ</a>
    </nav>

    <p class="support">Questions or something not working?
        <a id="support-link" href="#">Email support</a>
    </p>
</main>

<div id="app" class="app" hidden></div>
<div id="toasts" class="toasts" aria-live="polite"></div>
</body>
</html>
