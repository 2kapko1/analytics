<?php

namespace Tests\Feature;

use App\Models\PageVisit;
use App\Models\Url;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function createUrlAndVisit(string $fullUrl, string $date, int $visits = 1, int $uniqueVisits = 1): PageVisit
    {
        $parsed = Url::parseUrl($fullUrl);
        $url = Url::firstOrCreate([
            'base_path' => $parsed['base_path'],
            'url' => $parsed['url'],
        ]);

        return PageVisit::create([
            'url_id' => $url->id,
            'date' => $date,
            'visits' => $visits,
            'unique_visits' => $uniqueVisits,
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_displays_total_page_views(): void
    {
        $user = User::factory()->create();

        $this->createUrlAndVisit('https://example.vify.pl/page1', now()->toDateString(), 10, 10);
        $this->createUrlAndVisit('https://example.vify.pl/page2', now()->toDateString(), 5, 5);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('totalPageViews')
            ->where('totalPageViews', 15)
        );
    }

    public function test_dashboard_displays_time_series_data(): void
    {
        $user = User::factory()->create();

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $this->createUrlAndVisit('https://example.vify.pl/page1', $today, 10, 10);
        $this->createUrlAndVisit('https://example.vify.pl/page1', $yesterday, 5, 5);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('timeSeriesData')
            ->has('timeSeriesData', 2)
        );
    }

    public function test_dashboard_displays_url_stats(): void
    {
        $user = User::factory()->create();

        $this->createUrlAndVisit('https://example.vify.pl/page1', now()->toDateString(), 10, 10);
        $this->createUrlAndVisit('https://example.vify.pl/page2', now()->toDateString(), 3, 3);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('urlStats')
            ->has('urlStats', 2)
        );
    }

    public function test_dashboard_aggregates_data_across_dates(): void
    {
        $user = User::factory()->create();

        $this->createUrlAndVisit('https://example.vify.pl/page1', now()->toDateString(), 10, 10);
        $this->createUrlAndVisit('https://example.vify.pl/page1', now()->subDay()->toDateString(), 5, 5);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('urlStats', 1)
        );
    }

    public function test_dashboard_handles_empty_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('totalPageViews', 0)
            ->has('timeSeriesData', 0)
            ->has('urlStats', 0)
        );
    }
}
