<?php

namespace App\Http\Controllers;

use App\Models\SensorData;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Let search engines index the public pages only and point them to the sitemap.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /dashboard',
            'Disallow: /users',
            'Disallow: /login',
            'Disallow: /register',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * List the public pages; their last modification is the newest sensor reading.
     */
    public function sitemap(): Response
    {
        $lastModified = SensorData::query()->max('created_at') ?? now();

        $pages = [
            ['url' => route('visitor'), 'priority' => '1.0'],
            ['url' => route('prediction'), 'priority' => '0.8'],
            ['url' => route('app-download'), 'priority' => '0.6'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($pages as $page) {
            $xml .= '  <url>'
                .'<loc>'.e($page['url']).'</loc>'
                .'<lastmod>'.date('Y-m-d', strtotime((string) $lastModified)).'</lastmod>'
                .'<changefreq>hourly</changefreq>'
                .'<priority>'.$page['priority'].'</priority>'
                .'</url>'."\n";
        }

        return response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
