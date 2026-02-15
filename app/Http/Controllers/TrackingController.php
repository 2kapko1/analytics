<?php

namespace App\Http\Controllers;

use App\Models\PageVisit;
use App\Models\Url;
use Carbon\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TrackingController extends Controller
{
    public function track(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url',
        ]);

        $this->recordVisit($request->ip(), $validated['url']);

        return response()->json([
            'success' => true,
            'message' => 'Tracking data received',
        ]);
    }

    protected function recordVisit(string $ip, string $fullUrl): void
    {
        $parsed = Url::parseUrl($fullUrl);

        $url = Url::firstOrCreate([
            'base_path' => $parsed['base_path'],
            'url' => $parsed['url'],
        ]);

        $cacheKey = sprintf('tracking:%s:%s', now()->get(Unit::Month), $url->id);

        $date = now()->toDateString();
        $pageVisit = PageVisit::where('url_id', $url->id)
            ->where('date', $date)
            ->first();

        if ($pageVisit) {
            $pageVisit->increment('visits');

            if (!$this->isIpAlreadyTracked($cacheKey, $ip)){
                $pageVisit->increment('unique_visits');
                $this->trackIp($cacheKey, $ip);
            }
        } else {
            try {
                PageVisit::create([
                    'url_id' => $url->id,
                    'date' => $date,
                    'visits' => 1,
                    'unique_visits' => 1,
                ]);

                $this->trackIp($cacheKey, $ip);
            } catch (\Illuminate\Database\QueryException $e) {}
        }
    }

    protected function isIpAlreadyTracked(string $cacheKey, string $ip): bool
    {
        $trackedIps = Cache::get($cacheKey, []);
        return in_array($ip, $trackedIps, true);
    }

    protected function trackIp(string $cacheKey, string $ip): void
    {
        $trackedIps = Cache::get($cacheKey, []);
        $trackedIps[] = $ip;

        Cache::put(
            $cacheKey,
            $trackedIps,
            now()->endOfMonth()->diffInSeconds(now(), true)
        );
    }
}
