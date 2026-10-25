<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps browser-only pages (the Android app download) out of the Android app itself.
 */
class EnsureWebBrowser
{
    /**
     * Text the Median (GoNative) Android app appends to the WebView user agent (appConfig.json "userAgentAdd").
     */
    private const MOBILE_APP_USER_AGENT = 'median';

    /**
     * Send visitors inside the Android app back to the home page.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (self::isMobileApp($request)) {
            return redirect()->route('visitor');
        }

        return $next($request);
    }

    /**
     * Whether the request comes from the Android app instead of a web browser.
     */
    public static function isMobileApp(Request $request): bool
    {
        return str_contains(strtolower((string) $request->userAgent()), self::MOBILE_APP_USER_AGENT);
    }
}
