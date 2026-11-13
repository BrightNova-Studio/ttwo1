<?php

namespace App\Support;

class Client
{
    /**
     * The key used to rate-limit one visitor. IPv4 is used as is. An IPv6 visitor controls a whole /64 block
     * (billions of addresses), so every address in the same /64 counts as one visitor.
     */
    public static function key(?string $ip): string
    {
        $ip = (string) $ip;
        if (str_contains($ip, ':')) {
            $packed = @inet_pton($ip);
            if ($packed !== false && strlen($packed) === 16) {
                return 'v6:'.bin2hex(substr($packed, 0, 8));
            }
        }

        return $ip;
    }
}
