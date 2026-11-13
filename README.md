# 1two1 – private rooms for two

A tiny SaaS. Anyone can open a **room** themselves, no account and no operator needed: pick **Temp** or **Long term**
and get a Secret Room Key plus two Pass Codes. The two people enter key, Pass Code and a shared secret phrase, and chat.
Nobody else can read it, **including the operator**.

* **Temp**: the room is deleted for good 30 minutes after *both* members are away (any sign-in resets the clock).
* **Long term**: same rule with a 30-day clock.
* Operator console: usage statistics (daily / weekly / monthly / custom: created, auto-expired, active now), and block or delete any room. Counters live in `room_events`; rooms are hard-deleted.
* Public explainer pages: `/how-it-works`, `/temp-vs-long`, `/privacy`, `/faq`.
* Limits (`config/chat.php`, env): `CHAT_TEMP_SECONDS`, `CHAT_LONG_SECONDS`, `CHAT_CREATE_PER_HOUR`, `CHAT_CREATE_PER_DAY`, `CHAT_MAX_ROOMS`.
* Expiry runs from the scheduler (cron below) and also whenever the site is visited, so it works without cron too.

Stack: PHP 8.4, Laravel 13, MySQL/MariaDB in production (SQLite locally), plain JS + Vite (no third-party requests).

## How privacy works

| Layer | What happens |
|---|---|
| Messages, nicknames, reactions | Encrypted **in the browser** with AES-256-GCM. The key comes from the shared secret phrase (PBKDF2, 600k rounds) and never leaves the browser. The server stores only ciphertext. |
| Chat ID / Member IDs | Short and memorable: Chat ID = 2 letters + 4 digits (`KR-4821`), Member ID = 6 digits (`482-913`). Stored only as keyed hashes, shown to the operator **once**. Guessing is stopped by lockouts (8 wrong tries per chat → 15 min), a per-IP limit, and the secret phrase, which is what actually encrypts messages. |
| Sessions | Bearer token held in page memory only. No cookie, no localStorage. Close or reload the tab and the IDs are asked again. One live session per member; a new sign-in ends the old one. |
| Operator | Can create, disable, re-issue a Member ID, erase messages, delete a room. Has no screen, route or column that returns message content. |
| Browser | Strict CSP (nothing third-party), `no-store`, `no-referrer`, no framing, `noindex`. |
| Extras | Disappearing messages, view-once, unsend, clear chat for both, panic key (Esc Esc), blur on tab switch, idle auto sign-out. |

Limits that no website can remove: a screenshot/camera, malware on a member's device, or a member sharing their IDs and secret phrase. A website also cannot force Incognito mode; the entry page just shows a short tip to use Incognito / Private mode.

## Local development

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan operator:create you@example.com        # asks for a password (12+ chars)
npm run build && php artisan serve
```
Chat: `/`  Operator console: `/operator` (change with `OPERATOR_PATH`). Tests: `php artisan test`.

## Deploy (ServerByte / any shared host)

* Point the subdomain's document root at `public/` (or keep the app outside the web root and expose only `public/`).
* Set the domain's PHP version to **8.4** in the hosting panel.
* `.env` on the server: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://1two1.thesecretbasket.in`, MySQL `DB_*`, `SUPPORT_EMAIL`, a hard-to-guess `OPERATOR_PATH`, `SESSION_SECURE_COOKIE=true`.
* `composer install --no-dev --optimize-autoloader && php artisan migrate --force && php artisan config:cache route:cache view:cache`
* Cron (optional, sweeps expired disappearing messages in idle rooms): `* * * * * php /path/artisan schedule:run`
* `public/build` is committed because the server has no Node. Run `npm run build` locally before pushing.
