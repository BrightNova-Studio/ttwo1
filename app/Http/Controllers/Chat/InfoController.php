<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;

class InfoController extends Controller
{
    public function show(string $page)
    {
        abort_unless(in_array($page, ['how', 'modes', 'privacy', 'faq'], true), 404);

        return view('info.'.$page, [
            'supportEmail' => config('chat.support_email'),
            'temp' => (int) config('chat.modes.temp.seconds') / 60,
            'long' => (int) config('chat.modes.long.seconds') / 86400,
        ]);
    }
}
