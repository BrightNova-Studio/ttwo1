<?php

namespace App\Support;

/**
 * Chat IDs and member IDs are random, high-entropy and never stored in readable form:
 * the database only holds a keyed hash, so even the operator cannot read them back later.
 */
class Ids
{
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // no 0/O/1/I/L

    public static function generate(): string
    {
        $out = [];
        for ($g = 0; $g < 4; $g++) {
            $chunk = '';
            for ($i = 0; $i < 4; $i++) {
                $chunk .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $out[] = $chunk;
        }

        return implode('-', $out); // ~79 bits of entropy
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
