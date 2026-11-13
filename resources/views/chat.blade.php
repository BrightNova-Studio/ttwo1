<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#06060a">
    <meta name="referrer" content="no-referrer">
    @include('partials.seo', [
        'seoTitle' => '1two1: Private Encrypted Chat Rooms for Two, No Signup',
        'seoDesc' => 'Open a private, end-to-end encrypted chat room for two people in seconds. No account, no app, nothing saved. Temp rooms delete themselves, or meet someone new with Random connect.',
        'seoPath' => '/',
        'seoJson' => [
            ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => '1two1', 'url' => rtrim(config('app.url'), '/').'/', 'description' => 'Private, end-to-end encrypted chat rooms for exactly two people.'],
            ['@context' => 'https://schema.org', '@type' => 'WebApplication', 'name' => '1two1', 'url' => rtrim(config('app.url'), '/').'/', 'applicationCategory' => 'CommunicationApplication', 'operatingSystem' => 'Any (web browser)', 'browserRequirements' => 'Requires a modern browser with JavaScript and Web Crypto', 'inLanguage' => 'en', 'isAccessibleForFree' => true, 'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'], 'featureList' => ['End-to-end encrypted (AES-256-GCM)', 'No account or app needed', 'Temp rooms that delete themselves 30 minutes after both people leave', 'Long-term rooms that last 30 days', 'Random connect with strangers', 'View-once and disappearing messages']],
        ],
    ])
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @vite(['resources/js/chat/main.js'])
</head>
<body>
<div class="ambient" aria-hidden="true"><i class="orb o1"></i><i class="orb o2"></i><i class="orb o3"></i><i class="grain"></i></div>

<main id="gate" class="gate" data-support="{{ $supportEmail }}" data-temp="{{ $modes['temp'] }}" data-long="{{ $modes['long'] }}">
    <div id="tip" class="tip" hidden></div>

    @if (! empty($androidUpdate))
        {{-- Shown only inside the Android app, when a newer version has been published. --}}
        <a class="app-update" href="/downloads/{{ $androidUpdate['file'] }}">
            <b>Update available: {{ $androidUpdate['versionName'] }}</b>
            <span>You have {{ $androidUpdate['installed'] }}. Tap to download the new version.</span>
        </a>
    @endif

    <section class="card" aria-live="polite">
        <div class="brand">@include('partials.brand', ['u' => 'c'])</div>

        <h1 id="gate-title">A secret room for two.</h1>
        <p id="gate-sub" class="sub">End-to-end encrypted. No accounts, no cookies, nothing saved on this device.</p>

        <div id="gate-note" class="gate-note" hidden></div>

        <div id="tabs" class="tabs" role="tablist">
            <button id="tab-join" class="tab on" type="button" role="tab" aria-selected="true">Join a room</button>
            <button id="tab-new" class="tab" type="button" role="tab" aria-selected="false">Create</button>
            <button id="tab-rand" class="tab" type="button" role="tab" aria-selected="false">Random</button>
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
                <label for="secret" id="secret-label">Secret phrase <span class="opt">optional</span></label>
                <div class="inp" id="secret-field"><input id="secret" class="mask" name="s_{{ bin2hex(random_bytes(3)) }}" type="text" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" placeholder="Only the two of you know it" maxlength="40">
                    <button class="eye" type="button" data-reveal="secret" aria-label="Show or hide"></button></div>
                <p id="secret-hint" class="hint">This phrase never leaves your device. It locks your messages so nobody else, not even us, can read them. Agree on it with your partner privately. Use 5 to 40 characters; spaces count as one.</p>
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

        {{-- ------------------------------------------------- random connect --}}
        <div id="pane-rand" hidden>
            <form id="rand-form" novalidate>
                <div class="rand-intro">
                    <span class="rand-orb" aria-hidden="true"><i></i><i></i><i></i></span>
                    <div>
                        <b>Meet someone new</b>
                        <p>We pair you with another person who is looking right now, in a private Temp room. Anonymous and end-to-end encrypted. Either of you can leave at any moment.</p>
                    </div>
                </div>

                <label>What would you like to talk about?</label>
                <div class="topics" role="radiogroup" aria-label="Topic">
                    <button type="button" class="topic on" data-topic="any" role="radio" aria-checked="true">Anyone</button>
                    @foreach (config('chat.random_topics') as $k => $label)
                        <button type="button" class="topic" data-topic="{{ $k }}" role="radio" aria-checked="false">{{ $label }}</button>
                    @endforeach
                </div>

                <label class="agree"><input id="rand-adult" type="checkbox"><span>I am <b>18 or older</b>. I will be kind, and I will not share personal details, photos or money.</span></label>
                <p class="err" role="alert"></p>
                <button id="rand-go" class="btn primary block" type="submit" disabled><span>Look for someone</span></button>
                <p class="learnmore"><a href="/faq#random" target="_blank" rel="noopener">How random connect works and stays safe</a></p>
            </form>

            <div id="rand-wait" hidden>
                <div class="radar" aria-hidden="true"><i></i><i></i><i></i><span></span></div>
                <h3 id="rand-title">Looking for someone…</h3>
                <p id="rand-count" class="hint"></p>
                <p class="hint">Keep this tab open. When someone else is looking too, you are connected automatically.</p>
                <button id="rand-cancel" class="btn ghost block" type="button">Stop looking</button>
            </div>
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

    @if (! empty($android))
        <div class="getapp-wrap">
            <a class="getapp" href="/downloads/{{ $android['file'] }}" download="1two1-{{ $android['versionName'] }}.apk" rel="nofollow">
                <span class="getapp-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M17.6 9.48l1.84-3.18c.16-.31.04-.69-.26-.85a.637.637 0 00-.83.22l-1.88 3.24a11.463 11.463 0 00-8.94 0L5.65 5.67a.643.643 0 00-.87-.2c-.28.18-.37.54-.22.83L6.4 9.48A10.78 10.78 0 003 18h18a10.78 10.78 0 00-3.4-8.52zM7 15.25a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5zm10 0a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5z"/></svg></span>
                <span class="getapp-text"><b>Get the Android app</b><small>Version {{ $android['versionName'] }} &middot; {{ number_format($android['size'] / 1048576, 1) }} MB &middot; Android {{ $android['minAndroid'] }}+</small></span>
                <span class="getapp-dl" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11"/><path d="m7 11 5 5 5-5"/><path d="M5 20h14"/></svg></span>
            </a>
            <a class="getapp-more" href="/android" target="_blank" rel="noopener">Install steps, checksum and version history</a>
        </div>
    @endif

    <section class="about" aria-labelledby="about-h">
        <h2 id="about-h">Private chat rooms for two people</h2>
        <p>1two1 gives two people a private place to talk, with no sign-up and nothing to install. Messages are end-to-end encrypted in your browser, so not even we can read them.</p>
        <ul>
            <li><a href="/private-chat-room"><b>Private chat room</b><span>A locked room with exactly two keys.</span></a></li>
            <li><a href="/temporary-chat-room"><b>Temporary chat room</b><span>Deletes itself 30 minutes after you both leave.</span></a></li>
            <li><a href="/anonymous-chat"><b>Anonymous chat</b><span>Meet someone new, safely, with Random connect.</span></a></li>
        </ul>
    </section>

    <p class="support">Questions or something not working?
        <a id="support-link" href="#">Email support</a>
    </p>
</main>

<div id="app" class="app" hidden></div>
<div id="toasts" class="toasts" aria-live="polite"></div>
</body>
</html>
