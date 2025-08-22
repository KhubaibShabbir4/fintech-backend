<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SkipNgrokWarning
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Force ngrok to skip warning
        $response->headers->set('ngrok-skip-browser-warning', 'true');

        return $response;
    }
}
