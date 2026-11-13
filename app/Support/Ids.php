<?php

namespace App\Support;

/**
 * Chat IDs and member IDs are random, high-entropy and never stored in readable form:
 * the database only holds a keyed hash, so even the operator cannot read them back later.
 */
class Ids
{
    private const LETTERS = 'ABCDEFGHJKMNPQRSTUVWXYZ'; // no I, L, O

    /**
     * Chat ID: two letters + four digits, e.g. KR-4821. Easy to remember; the letters keep the
     * space large enough for millions of rooms. Guessing is stopped by lockouts, not by length.
     */
    public static function chat(): string
    {
        $n = strlen(self::LETTERS) - 1;

        return self::LETTERS[random_int(0, $n)].self::LETTERS[random_int(0, $n)]
            .'-'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /** Member ID: six digits, e.g. 482-913. */
    public static function member(): string
    {
        $d = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return substr($d, 0, 3).'-'.substr($d, 3);
    }

    /** A Chat ID that no room uses yet. */
    public static function freshChat(): string
    {
        do {
            $id = self::chat();
        } while (\App\Models\Room::where('chat_hash', self::hash($id))->exists());

        return $id;
    }

    /** Two different Member IDs for one room. */
    public static function memberPair(): array
    {
        do {
            $a = self::member();
            $b = self::member();
        } while (self::normalize($a) === self::normalize($b));

        return [$a, $b];
    }

    public static function normalize(?string $id): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $id));
    }

    public static function hash(?string $id): string
    {
        return hash_hmac('sha256', self::normalize($id), (string) config('app.key'));
    }

    public static function token(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(40)), '+/', '-_'), '=');
    }

    public static function tokenHash(string $token): string
    {
        return hash('sha256', $token);
    }
}
