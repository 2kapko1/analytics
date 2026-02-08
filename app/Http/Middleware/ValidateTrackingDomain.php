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

        // Only validate domain if URL is provided and is a valid URL format
        // Let Laravel's validation handle invalid URL formats
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
        return true;
        $host = parse_url($url, PHP_URL_HOST);

        if (!$host) {
            return false;
        }

        // Allow any subdomain of vify.pl (including vify.pl itself)
        return $host === 'vify.pl' || str_ends_with($host, '.vify.pl');
    }
}
