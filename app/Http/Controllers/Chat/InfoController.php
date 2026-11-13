<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InfoController extends Controller
{
    /** Public, indexable pages: slug => [url path, view, sitemap priority]. */
    public const PAGES = [
        'private' => ['/private-chat-room', 'info.private', '0.9'],
        'anonymous' => ['/anonymous-chat', 'info.anonymous', '0.9'],
        'temporary' => ['/temporary-chat-room', 'info.temporary', '0.9'],
        'how' => ['/how-it-works', 'info.how', '0.8'],
        'modes' => ['/temp-vs-long', 'info.modes', '0.7'],
        'privacy' => ['/privacy', 'info.privacy', '0.6'],
        'faq' => ['/faq', 'info.faq', '0.7'],
    ];

    public function show(Request $request, string $page)
    {
        abort_unless(isset(self::PAGES[$page]), 404);
        $request->attributes->set('seo', ['index' => true, 'cache' => true]);

        return view(self::PAGES[$page][1], [
            'path' => self::PAGES[$page][0],
            'supportEmail' => config('chat.support_email'),
            'temp' => (int) config('chat.modes.temp.seconds') / 60,
            'long' => (int) config('chat.modes.long.seconds') / 86400,
        ]);
    }

    /** sitemap.xml: every public page, with the date its template last changed. */
    public function sitemap(Request $request)
    {
        $request->attributes->set('seo', ['cache' => true]);
        $base = rtrim(config('app.url'), '/');
        $home = max(filemtime(resource_path('views/chat.blade.php')), filemtime(resource_path('views/partials/seo.blade.php')));

        $urls = [['loc' => $base.'/', 'lastmod' => date('Y-m-d', $home), 'priority' => '1.0']];
        foreach (self::PAGES as [$path, $view, $priority]) {
            $urls[] = [
                'loc' => $base.$path,
                'lastmod' => date('Y-m-d', filemtime(resource_path('views/'.str_replace('.', '/', $view).'.blade.php'))),
                'priority' => $priority,
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url><loc>{$u['loc']}</loc><lastmod>{$u['lastmod']}</lastmod><priority>{$u['priority']}</priority></url>\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
