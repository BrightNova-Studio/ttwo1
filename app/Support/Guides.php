<?php

namespace App\Support;

/**
 * The three how-to videos shown on the home page (website and Android app alike).
 * A video is listed only when its file is actually on the server, so a half-deployed release never shows a dead player.
 * Files live in public/videos/: <slug>.mp4, <slug>.vtt (captions), <slug>-poster.jpg and videos.json (length and size).
 */
class Guides
{
    private const ITEMS = [
        'long-term-room' => [
            'title' => 'Private chat room',
            'info' => 'A locked room with exactly two keys. Keep it for up to 30 days.',
            'video' => 'Long-term rooms: create one and learn every setting',
            'page' => '/private-chat-room',
        ],
        'temp-room' => [
            'title' => 'Temporary chat room',
            'info' => 'Deletes itself 30 minutes after you both leave.',
            'video' => 'Temp rooms: create, share and join',
            'page' => '/temporary-chat-room',
        ],
        'random-connect' => [
            'title' => 'Anonymous chat',
            'info' => 'Meet someone new, safely, with Random connect.',
            'video' => 'Random connect: meet someone new, safely',
            'page' => '/anonymous-chat',
        ],
    ];

    /** Search-engine markup (schema.org VideoObject) for the videos that are really there. */
    public static function jsonLd(array $guides): array
    {
        $base = rtrim((string) config('app.url'), '/');

        return array_values(array_map(fn ($g) => [
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $g['video'],
            'description' => $g['title'].': '.$g['info'],
            'thumbnailUrl' => $base.$g['poster'],
            'uploadDate' => '2026-11-13',
            'duration' => $g['iso'],
            'contentUrl' => $base.$g['mp4'],
            'inLanguage' => 'en',
        ], array_filter($guides, fn ($g) => $g['ready'])));
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $dir = public_path('videos');
        $manifest = is_file($dir.'/videos.json') ? (json_decode((string) file_get_contents($dir.'/videos.json'), true) ?: []) : [];

        $out = [];
        foreach (self::ITEMS as $slug => $item) {
            $ready = is_file("$dir/$slug.mp4") && is_file("$dir/$slug-poster.jpg") && is_file("$dir/$slug-thumb.jpg");
            $seconds = (int) ($manifest[$slug]['seconds'] ?? 0);
            $out[] = $item + [
                'slug' => $slug,
                'ready' => $ready,
                'seconds' => $seconds,
                'length' => sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60),
                'iso' => 'PT'.intdiv($seconds, 60).'M'.($seconds % 60).'S',
                'mp4' => "/videos/$slug.mp4",
                'vtt' => is_file("$dir/$slug.vtt") ? "/videos/$slug.vtt" : null,
                'poster' => "/videos/$slug-poster.jpg",
                'thumb' => "/videos/$slug-thumb.jpg",
            ];
        }

        return $out;
    }
}
