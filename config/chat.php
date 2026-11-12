<?php

return [
    // Shown on the entry page so people can ask for a new chat ID. Set SUPPORT_EMAIL in .env.
    'support_email' => env('SUPPORT_EMAIL', '1two1@thesecretbasket.com'),

    // The operator console lives at this path. Pick something unguessable in production.
    'operator_path' => trim((string) env('OPERATOR_PATH', 'operator'), '/'),

    // A member token that has not polled for this long is dead (tab closed, laptop asleep...).
    'session_idle_seconds' => (int) env('CHAT_SESSION_IDLE', 90),

    // Sign-in brute-force protection.
    'max_failures' => (int) env('CHAT_MAX_FAILURES', 8),
    'lockout_minutes' => (int) env('CHAT_LOCKOUT_MINUTES', 15),

    'max_body_chars' => 24000,
    'disappear_options' => [0, 30, 300, 3600, 86400],
];
