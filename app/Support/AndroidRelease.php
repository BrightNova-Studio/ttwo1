<?php

namespace App\Support;

/**
 * The published Android app: what version is current, its checksum and signing fingerprint.
 * scripts/release.mjs writes public/downloads/android.json; the website only ever reads it.
 */
class AndroidRelease
{
    public static function current(): ?array
    {
        $file = public_path('downloads/android.json');
        if (! is_file($file)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($file), true);

        // Only show a download that really exists on the server.
        if (! is_array($data) || empty($data['file']) || ! is_file(public_path('downloads/'.$data['file']))) {
            return null;
        }

        return $data;
    }

    /** The app announces itself in its User-Agent as "1two1-android/1.2.3 (12)". */
    public static function fromUserAgent(?string $ua): ?array
    {
        if ($ua && preg_match('#1two1-android/(\d+\.\d+\.\d+) \((\d+)\)#', $ua, $m)) {
            return ['versionName' => $m[1], 'versionCode' => (int) $m[2]];
        }

        return null;
    }

    /** Release history from android-app/CHANGELOG.md, newest first. */
    public static function history(): array
    {
        $file = base_path('android-app/CHANGELOG.md');
        if (! is_file($file)) {
            return [];
        }
        $out = [];
        foreach (preg_split('/^## /m', (string) file_get_contents($file)) as $i => $block) {
            if ($i === 0 || ! preg_match('/^(\d+\.\d+\.\d+) \(code (\d+)\), (\d{4}-\d{2}-\d{2})\R(.*)$/s', $block, $m)) {
                continue;
            }
            $notes = trim(preg_replace('/^- APK SHA-256:.*$/m', '', $m[4]));
            $out[] = ['version' => $m[1], 'code' => (int) $m[2], 'date' => $m[3], 'notes' => ltrim(trim($notes), "- \t")];
        }

        return $out;
    }
}
