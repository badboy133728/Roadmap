<?php

namespace App\Http\Middleware;

use App\Services\CityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CachePublicPages
{
    private const TTL = 300;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || auth()->check()) {
            return $next($request);
        }

        $key = 'page:' . sha1($request->fullUrl() . '|' . $request->cookie(CityService::COOKIE_NAME, 'default'));

        $cached = Cache::get($key);

        if (is_string($cached)) {
            return response($cached)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('X-Page-Cache', 'HIT');
        }

        $response = $next($request);

        if ($response->isSuccessful() && str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            Cache::put($key, $response->getContent(), self::TTL);
            $response->headers->set('X-Page-Cache', 'MISS');
        }

        return $response;
    }
}
