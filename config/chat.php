<?php

return [
    // Shown on the entry page so people can ask for a new chat ID. Set SUPPORT_EMAIL in .env.
    'support_email' => env('SUPPORT_EMAIL', '1two1@thesecretbasket.in'),

    // The operator console lives at this path. Pick something unguessable in production.
    'operator_path' => trim((string) env('OPERATOR_PATH', 'operator'), '/'),

    // A member token that has not polled for this long is dead (tab closed, laptop asleep...).
    'session_idle_seconds' => (int) env('CHAT_SESSION_IDLE', 90),

    // Sign-in brute-force protection.
    'max_failures' => (int) env('CHAT_MAX_FAILURES', 8),
    'lockout_minutes' => (int) env('CHAT_LOCKOUT_MINUTES', 15),

    // Room modes. A room is deleted for good once BOTH members have been away this long.
    'modes' => [
        'temp' => ['label' => 'Temp', 'seconds' => (int) env('CHAT_TEMP_SECONDS', 30 * 60)],
        'long' => ['label' => 'Long term', 'seconds' => (int) env('CHAT_LONG_SECONDS', 30 * 86400)],
    ],

    // Anyone can create a room. These limits stop one person from flooding the service.
    'create_per_hour' => (int) env('CHAT_CREATE_PER_HOUR', 6),     // per IP address
    'create_per_day' => (int) env('CHAT_CREATE_PER_DAY', 20),      // per IP address
    'max_rooms' => (int) env('CHAT_MAX_ROOMS', 20000),             // whole service

    // Random connect: pairs two strangers who are looking at the same moment into a fresh Temp room.
    'random_topics' => [
        'chat' => 'Just chatting',
        'advice' => 'Advice',
        'games' => 'Games',
        'music' => 'Music',
        'movies' => 'Movies & shows',
        'tech' => 'Tech',
        'books' => 'Books',
    ],
    'random_joins_per_hour' => (int) env('CHAT_RANDOM_PER_HOUR', 30),   // per IP address

    'max_body_chars' => 24000,
    'disappear_options' => [0, 30, 300, 3600, 86400],
];
