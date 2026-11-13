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

    // Invisible anti-automation check before a room is created / a random search starts (see App\Support\Challenge).
    // "bits" = leading zero bits the browser must find; each extra bit doubles the work.
    // 14 bits is a fraction of a second on a phone and is solved in the background while the person reads the page.
    'pow' => [
        'enabled' => (bool) env('CHAT_POW', true),
        'base_bits' => (int) env('CHAT_POW_BITS', 14),
        'step_bits' => 1,          // extra bit (double the work) for every room this visitor already made in the last hour
        'max_step_bits' => 8,
        'cap_bits' => 26,
        'ttl' => 600,              // seconds a solved challenge stays valid
        'surge_low' => (int) env('CHAT_POW_SURGE_LOW', 30),     // site-wide new rooms per minute: puzzles get a little harder
        'surge_high' => (int) env('CHAT_POW_SURGE_HIGH', 80),   // ... much harder
        'pause_at' => (int) env('CHAT_POW_PAUSE', 200),         // ... new rooms pause for a minute ("busy")
    ],

    'max_body_chars' => 24000,
    'disappear_options' => [0, 30, 300, 3600, 86400],

    // Vanish mode: a message is erased this many seconds after the other person has seen it.
    'vanish_seconds' => 30,
];
