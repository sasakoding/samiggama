<?php

namespace App\Http\Middleware;

use App\Models\VisitorLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitorMiddleware
{
    /**
     * Handle an incoming request and track page view seamlessly.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Record page view after response is generated for optimal speed & non-blocking execution
        try {
            VisitorLog::recordVisit($request);
        } catch (\Throwable $e) {
            // Silently catch so visitor logging never breaks user requests
        }

        return $response;
    }
}
