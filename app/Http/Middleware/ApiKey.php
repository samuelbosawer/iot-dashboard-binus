<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKey
{
    /**
     * Handle an incoming request by validating the x-api-key header.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = (string) config('services.iot.api_key');
        $providedKey = (string) $request->header('x-api-key');

        if ($expectedKey === '' || ! hash_equals($expectedKey, $providedKey)) {
            return response()->json(['message' => 'Invalid or missing API key.'], 401);
        }

        return $next($request);
    }
}
