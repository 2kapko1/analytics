<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTrackingDomain
{
    /**
     * Handle an incoming request.
     *
     * Validates that the request URL belongs to an allowed domain (*.vify.pl).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $url = $request->input('url');

        if ($url && filter_var($url, FILTER_VALIDATE_URL) && !$this->isAllowedDomain($url)) {
            return response()->json([
                'success' => false,
                'message' => 'Domain not allowed for tracking',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if the URL belongs to an allowed domain (*.vify.pl).
     */
    protected function isAllowedDomain(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!$host) {
            return false;
        }

        return $host === 'vify.pl' || str_ends_with($host, '.vify.pl') || $host === 'visitify.pl' || str_ends_with($host, '.visitify.pl');
    }
}
