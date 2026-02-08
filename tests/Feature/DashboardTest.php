<?php

namespace Tests\Feature;

use App\Models\PageVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

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

        PageVisit::create([
            'url' => 'https://example.com/page1',
            'date' => now()->toDateString(),
            'count' => 10,
        ]);

        PageVisit::create([
            'url' => 'https://example.com/page2',
            'date' => now()->toDateString(),
            'count' => 5,
        ]);

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

        PageVisit::create([
            'url' => 'https://example.com/page1',
            'date' => $today,
            'count' => 10,
        ]);

        PageVisit::create([
            'url' => 'https://example.com/page1',
            'date' => $yesterday,
            'count' => 5,
        ]);

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

        PageVisit::create([
            'url' => 'https://example.com/page1',
            'date' => now()->toDateString(),
            'count' => 10,
        ]);

        PageVisit::create([
            'url' => 'https://example.com/page2',
            'date' => now()->toDateString(),
            'count' => 3,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('urlStats')
            ->has('urlStats', 2)
            ->where('urlStats.0.url', 'https://example.com/page1')
            ->where('urlStats.0.count', 10)
            ->where('urlStats.1.url', 'https://example.com/page2')
            ->where('urlStats.1.count', 3)
        );
    }

    public function test_dashboard_aggregates_data_across_dates(): void
    {
        $user = User::factory()->create();

        PageVisit::create([
            'url' => 'https://example.com/page1',
            'date' => now()->toDateString(),
            'count' => 10,
        ]);

        PageVisit::create([
            'url' => 'https://example.com/page1',
            'date' => now()->subDay()->toDateString(),
            'count' => 5,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('urlStats.0.count', 15)
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
