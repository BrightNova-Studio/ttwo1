<?php

namespace App\Support;

use App\Models\RoomEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Invisible anti-automation check ("proof of work"), used before a room is created or a random search starts.
 *
 * The browser asks for a challenge and quietly searches for a number whose SHA-256 hash (with the challenge)
 * starts with N zero bits. For a person that costs a fraction of a second, done in the background while they read
 * the page. For a bot making thousands of rooms it is thousands of puzzles, so it is slow and expensive.
 *
 *  - Difficulty (N) is chosen by the SERVER and grows with every room this visitor already made in the last hour.
 *  - If the whole site suddenly gets too many new rooms per minute, every puzzle gets harder; far beyond that,
 *    creation pauses for a moment ("busy"). Existing chats keep working.
 *  - A challenge is signed (HMAC), expires, and can be used once.
 *
 * No cookies, no third party, no tracking.
 */
class Challenge
{
    public static function enabled(): bool
    {
        return (bool) config('chat.pow.enabled');
    }

    /** New rooms (and random searches) started anywhere on the site during the last full or current minute. */
    public static function surge(): int
    {
        $m = (int) floor(time() / 60);

        return max((int) Cache::get('pow-surge:'.$m, 0), (int) Cache::get('pow-surge:'.($m - 1), 0));
    }

    public static function paused(): bool
    {
        if (! self::enabled() || self::surge() < (int) config('chat.pow.pause_at')) {
            return false;
        }
        if (Cache::add('pow-brake-logged:'.floor(time() / 60), 1, 120)) {
            RoomEvent::record('throttled', 'temp', 'self'); // the operator Statistics page counts these
        }

        return true;
    }

    /** Leading zero bits the visitor must find right now. */
    public static function bits(string $purpose, string $client): int
    {
        $hits = (int) Cache::get("pow-hits:$purpose:$client", 0);
        $bits = (int) config('chat.pow.base_bits') + min($hits * (int) config('chat.pow.step_bits'), (int) config('chat.pow.max_step_bits'));

        $surge = self::surge();
        if ($surge >= (int) config('chat.pow.surge_high')) {
            $bits += 4;
        } elseif ($surge >= (int) config('chat.pow.surge_low')) {
            $bits += 2;
        }

        return min($bits, (int) config('chat.pow.cap_bits'));
    }

    /** Hand out a signed, expiring challenge. */
    public static function issue(string $purpose, string $client): array
    {
        $bits = self::bits($purpose, $client);
        $b64 = self::b64(json_encode(['p' => $purpose, 'r' => bin2hex(random_bytes(12)), 'b' => $bits, 'e' => time() + (int) config('chat.pow.ttl')]));

        return ['token' => $b64.'.'.self::sign($b64), 'bits' => $bits, 'expires_in' => (int) config('chat.pow.ttl')];
    }

    /** Returns null when the request carries a valid, unused solution; otherwise the JSON error to send back. */
    public static function verify(Request $request, string $purpose, string $client): ?JsonResponse
    {
        if (! self::enabled()) {
            return null;
        }

        if (self::paused()) {
            return self::fail('busy', 'We are very busy right now. Please try again in a minute.', 503);
        }

        // A hidden form field that people never see. Automation that fills in every field gives itself away.
        if ((string) $request->input('hp', '') !== '') {
            return self::fail('check_failed', 'We could not verify this request. Please reload the page and try again.', 422);
        }

        $token = (string) $request->input('pow_token', '');
        $nonce = (string) $request->input('pow_nonce', '');
        if ($token === '' || $nonce === '' || strlen($token) > 600 || strlen($nonce) > 40 || ! ctype_digit($nonce)) {
            return self::fail('pow_required', 'A quick security check is needed. Please try again.', 422);
        }

        [$b64, $sig] = array_pad(explode('.', $token, 2), 2, '');
        if (! hash_equals(self::sign($b64), $sig)) {
            return self::fail('pow_invalid', 'The security check was not valid. Please try again.', 422);
        }
        $claims = json_decode((string) base64_decode(strtr($b64, '-_', '+/')), true);
        if (! is_array($claims) || ($claims['p'] ?? '') !== $purpose || (int) ($claims['e'] ?? 0) < time()) {
            return self::fail('pow_stale', 'The security check expired. Trying again.', 422);
        }

        // The visitor may have made more rooms since the challenge was issued: the bar may have moved up.
        $need = self::bits($purpose, $client);
        $has = (int) ($claims['b'] ?? 0);
        if ($has < $need) {
            return self::fail('pow_stale', 'A stronger check is needed. Trying again.', 422);
        }

        $hash = hash('sha256', $token.':'.$nonce, true);
        if (self::zeroBits($hash) < $has) {
            return self::fail('pow_invalid', 'The security check was not valid. Please try again.', 422);
        }

        // One use only.
        if (! Cache::add('pow-used:'.sha1($token), 1, (int) config('chat.pow.ttl') + 60)) {
            return self::fail('pow_used', 'That security check was already used. Trying again.', 422);
        }

        // Count it: this visitor's next puzzle is harder, and so is everybody's if the whole site is flooded.
        $hitKey = "pow-hits:$purpose:$client";
        Cache::add($hitKey, 0, 3600);
        Cache::increment($hitKey);
        $minute = 'pow-surge:'.floor(time() / 60);
        Cache::add($minute, 0, 180);
        Cache::increment($minute);

        return null;
    }

    public static function zeroBits(string $binary): int
    {
        $n = 0;
        for ($i = 0, $len = strlen($binary); $i < $len; $i++) {
            $byte = ord($binary[$i]);
            if ($byte === 0) {
                $n += 8;
                continue;
            }

            return $n + 8 - strlen(decbin($byte));
        }

        return $n;
    }

    private static function fail(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => $code, 'message' => $message], $status);
    }

    private static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function sign(string $b64): string
    {
        return hash_hmac('sha256', $b64, (string) config('app.key'));
    }
}
