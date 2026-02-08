<?php

namespace App\Http\Controllers;

use App\Models\PageVisit;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the analytics dashboard.
     */
    public function index(): Response
    {
        // Get total page views
        $totalPageViews = PageVisit::sum('count');

        // Get visits over time (last 30 days)
        $timeSeriesData = PageVisit::select(
            'date',
            DB::raw('SUM("count") as total')
        )
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date->toDateString(),
                    'count' => (int) $item->total,
                ];
            });

        // Get URL statistics (aggregated by URL)
        $urlStats = PageVisit::select(
            'url',
            DB::raw('SUM("count") as total')
        )
            ->groupBy('url')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'url' => $item->url,
                    'count' => (int) $item->total,
                ];
            });

        return Inertia::render('Dashboard', [
            'totalPageViews' => (int) $totalPageViews,
            'timeSeriesData' => $timeSeriesData,
            'urlStats' => $urlStats,
        ]);
    }
}
