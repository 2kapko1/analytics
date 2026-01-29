<?php

namespace App\Http\Controllers;

use App\Models\PageVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TrackingController extends Controller
{
    /**
     * Handle incoming tracking data from the tracking script.
     */
    public function track(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:visit,download',
            'url' => 'required|url',
            'downloadUrl' => 'nullable|url',
        ]);

        $type = $validated['type'];
        $url = $validated['url'];
        $ip = $request->ip();
        $today = now()->toDateString();

        // Generate cache key for date/type/url combination (stores array of IPs)
        $cacheKey = $this->generateCacheKey($url, $type, $today);

        // Check if this IP has already been recorded for this URL/type today
        if ($this->isIpAlreadyTracked($cacheKey, $ip)) {
            return response()->json([
                'success' => true,
                'message' => 'Tracking data received',
            ]);
        }

        // Add IP to the tracked IPs array for today
        $this->trackIp($cacheKey, $ip);

        // Update or create the page visit record
        $this->recordVisit($url, $today, $type);

        return response()->json([
            'success' => true,
            'message' => 'Tracking data received',
        ]);
    }

    /**
     * Generate a cache key for date/type/url combination.
     * IPs are stored as an array within this key.
     */
    protected function generateCacheKey(string $url, string $type, string $date): string
    {
        return sprintf('tracking:%s:%s:%s', $date, $type, md5($url));
    }

    /**
     * Check if the IP has already been tracked for this cache key.
     */
    protected function isIpAlreadyTracked(string $cacheKey, string $ip): bool
    {
        $trackedIps = Cache::get($cacheKey, []);
        return in_array($ip, $trackedIps, true);
    }

    /**
     * Add IP to the tracked IPs array in cache.
     */
    protected function trackIp(string $cacheKey, string $ip): void
    {
        $trackedIps = Cache::get($cacheKey, []);
        $trackedIps[] = $ip;

        // Cache expires at midnight (end of day)
        $secondsUntilMidnight = now()->endOfDay()->diffInSeconds(now());
        Cache::put($cacheKey, $trackedIps, $secondsUntilMidnight);
    }

    /**
     * Record the visit or download in the database.
     */
    protected function recordVisit(string $url, string $date, string $type): void
    {
        // Use upsert-style approach to handle concurrent requests
        $pageVisit = PageVisit::where('url', $url)
            ->where('date', $date)
            ->first();

        if ($pageVisit) {
            $pageVisit->increment('count');
        } else {
            try {
                PageVisit::create([
                    'url' => $url,
                    'date' => $date,
                    'count' => 1,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Handle race condition - record was created by another request
                $pageVisit = PageVisit::where('url', $url)
                    ->where('date', $date)
                    ->first();
                if ($pageVisit) {
                    $pageVisit->increment('count');
                }
            }
        }
    }
}
