<?php

namespace App\Http\Middleware;

use App\Support\Acquisition;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers ?utm_source=… from the first visit that carries one (first touch), before anything
 * can redirect and drop the query string — e.g. a guest hitting /?utm_source=tv is sent to /login.
 */
class CaptureAcquisitionSource
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->has('utm_source') && ! $request->cookie(Acquisition::COOKIE)) {
            $source = Acquisition::normalize((string) $request->query('utm_source'));

            if ($source !== '') {
                Cookie::queue(Cookie::make(Acquisition::COOKIE, $source, Acquisition::COOKIE_MINUTES, '/', null, null, true, false, 'lax'));
            }
        }

        return $next($request);
    }
}
