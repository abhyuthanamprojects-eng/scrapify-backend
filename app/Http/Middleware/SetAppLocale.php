<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetAppLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = config('app.locale');

        // 1. Check authenticated user preference (Initial Base)
        if (Auth::check() && Auth::user()->language) {
            $locale = Auth::user()->language;
        }

        // 2. Check Accept-Language / X-App-Language header (Override)
        if ($headerLocale = $request->header('X-App-Language')) {
            $locale = $headerLocale;
        } elseif ($acceptLocale = $request->header('Accept-Language')) {
            // Simplified: take first part of Accept-Language like 'en-US' -> 'en'
            $locale = substr($acceptLocale, 0, 2);
        }

        // Validate supported locales
        $supported = ['en', 'hi', 'gu'];
        if (in_array($locale, $supported)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
