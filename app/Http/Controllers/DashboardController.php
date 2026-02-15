<?php

namespace App\Http\Controllers;

use App\Models\PageVisit;
use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the analytics dashboard.
     */
    public function index(Request $request): Response
    {
        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $basePath = $request->query('base_path');
        $basePaths = Url::distinct()->orderBy('base_path')->pluck('base_path');

        $basePathFilter = function ($query) use ($basePath) {
            if ($basePath) {
                $query->whereHas('url', fn ($q) => $q->where('base_path', $basePath));
            }
        };

        // Total page views (all time)
        $totalPageViews = PageVisit::when($basePath, $basePathFilter)->sum('unique_visits');

        $totalsToday = PageVisit::query()
            ->when($basePath, $basePathFilter)
            ->where('date', $today)
            ->selectRaw('COALESCE(SUM(visits), 0) as visits')
            ->selectRaw('COALESCE(SUM(unique_visits), 0) as unique_visits')
            ->first();

        $totalsMonth = PageVisit::query()
            ->when($basePath, $basePathFilter)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->selectRaw('COALESCE(SUM(visits), 0) as visits')
            ->selectRaw('COALESCE(SUM(unique_visits), 0) as unique_visits')
            ->first();

        // Get visits over time (last 30 days)
        $timeSeriesData = PageVisit::when($basePath, $basePathFilter)
            ->select(
            'date',
            DB::raw('SUM(visits) as total_visits'),
            DB::raw('SUM(unique_visits) as total_unique_visits')
        )
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date->toDateString(),
                    'visits' => (int) $item->total_visits,
                    'uniqueVisits' => (int) $item->total_unique_visits,
                ];
            });

        // Get URL statistics (aggregated by URL)
        $urlStats = PageVisit::query()
            ->join('urls', 'page_visits.url_id', '=', 'urls.id')
            ->when($basePath, fn ($query) => $query->where('urls.base_path', $basePath))
            ->select(DB::raw("CONCAT(urls.base_path, urls.url) as url"))
            ->selectRaw('COALESCE(SUM(page_visits.visits), 0) as total_visits')
            ->selectRaw('COALESCE(SUM(page_visits.unique_visits), 0) as total_unique_visits')
            ->selectRaw('COALESCE(SUM(CASE WHEN page_visits.date = ? THEN page_visits.visits ELSE 0 END), 0) as today_visits', [$today])
            ->selectRaw('COALESCE(SUM(CASE WHEN page_visits.date = ? THEN page_visits.unique_visits ELSE 0 END), 0) as today_unique_visits', [$today])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN page_visits.date BETWEEN ? AND ? THEN page_visits.visits ELSE 0 END), 0) as month_visits',
                [$startOfMonth, $endOfMonth]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN page_visits.date BETWEEN ? AND ? THEN page_visits.unique_visits ELSE 0 END), 0) as month_unique_visits',
                [$startOfMonth, $endOfMonth]
            )
            ->groupBy('urls.base_path', 'urls.url')
            ->orderByDesc('month_unique_visits')
            ->get()
            ->map(function ($item) {
                return [
                    'url' => $item->url,
                    'totalVisits' => (int) $item->total_visits,
                    'totalUniqueVisits' => (int) $item->total_unique_visits,
                    'todayVisits' => (int) $item->today_visits,
                    'todayUniqueVisits' => (int) $item->today_unique_visits,
                    'monthVisits' => (int) $item->month_visits,
                    'monthUniqueVisits' => (int) $item->month_unique_visits,
                ];
            });

        return Inertia::render('Dashboard', [
            'basePaths' => $basePaths,
            'currentBasePath' => $basePath,
            'totalPageViews' => (int) $totalPageViews,
            'totalsToday' => [
                'visits' => (int) ($totalsToday->visits ?? 0),
                'uniqueVisits' => (int) ($totalsToday->unique_visits ?? 0),
            ],
            'totalsMonth' => [
                'visits' => (int) ($totalsMonth->visits ?? 0),
                'uniqueVisits' => (int) ($totalsMonth->unique_visits ?? 0),
            ],
            'timeSeriesData' => $timeSeriesData,
            'urlStats' => $urlStats,
        ]);
    }
}
