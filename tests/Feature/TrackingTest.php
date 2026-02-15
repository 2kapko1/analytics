<?php

namespace Tests\Feature;

use App\Models\PageVisit;
use App\Models\Url;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_track_endpoint_accepts_visit_event(): void
    {
        $response = $this->postJson('/api/track', [
            'url' => 'https://test.vify.pl/page',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tracking data received',
            ]);
    }

    public function test_track_endpoint_requires_url(): void
    {
        $response = $this->postJson('/api/track', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_track_endpoint_validates_url_format(): void
    {
        $response = $this->postJson('/api/track', [
            'url' => 'not-a-valid-url',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_visit_creates_database_record(): void
    {
        $this->postJson('/api/track', [
            'url' => 'https://test.vify.pl/page',
        ]);

        $url = Url::where('base_path', 'test.vify.pl')->where('url', '/page')->first();
        $this->assertNotNull($url);

        $record = PageVisit::where('url_id', $url->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals(now()->toDateString(), $record->date->toDateString());
        $this->assertEquals(1, $record->visits);
    }

    public function test_multiple_requests_from_same_ip_only_count_once_for_unique(): void
    {
        $url = 'https://test.vify.pl/unique-test';

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/track', [
                'url' => $url,
            ]);
        }

        $urlRecord = Url::where('base_path', 'test.vify.pl')->where('url', '/unique-test')->first();
        $record = PageVisit::where('url_id', $urlRecord->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals(1, $record->unique_visits);
        $this->assertEquals(5, $record->visits);

        $this->assertDatabaseCount('page_visits', 1);
    }

    public function test_different_urls_are_tracked_separately(): void
    {
        $this->postJson('/api/track', [
            'url' => 'https://test.vify.pl/page1',
        ]);

        $this->postJson('/api/track', [
            'url' => 'https://test.vify.pl/page2',
        ]);

        $this->assertDatabaseCount('page_visits', 2);
    }

    public function test_ip_addresses_are_not_stored_in_database(): void
    {
        $this->postJson('/api/track', [
            'url' => 'https://test.vify.pl/privacy-test',
        ]);

        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('page_visits');
        $this->assertNotContains('ip', $columns);
        $this->assertNotContains('ip_address', $columns);

        $record = PageVisit::first();
        $this->assertNotNull($record);
        $attributes = $record->getAttributes();
        $this->assertArrayNotHasKey('ip', $attributes);
        $this->assertArrayNotHasKey('ip_address', $attributes);
    }

    public function test_rejects_non_vify_pl_domains(): void
    {
        $response = $this->postJson('/api/track', [
            'url' => 'https://example.com/page',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Domain not allowed for tracking',
            ]);

        $this->assertDatabaseCount('page_visits', 0);
    }

    public function test_accepts_vify_pl_root_domain(): void
    {
        $response = $this->postJson('/api/track', [
            'url' => 'https://vify.pl/page',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('page_visits', 1);
    }

    public function test_accepts_any_vify_pl_subdomain(): void
    {
        $this->postJson('/api/track', [
            'url' => 'https://app.vify.pl/page',
        ])->assertStatus(200);

        $this->postJson('/api/track', [
            'url' => 'https://deep.sub.vify.pl/page',
        ])->assertStatus(200);

        $this->assertDatabaseCount('page_visits', 2);
    }

    public function test_rejects_domains_ending_with_vify_pl_but_not_subdomain(): void
    {
        $response = $this->postJson('/api/track', [
            'url' => 'https://notvify.pl/page',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('page_visits', 0);
    }

    public function test_urls_with_and_without_www_are_treated_as_same(): void
    {
        $this->postJson('/api/track', [
            'url' => 'https://www.vify.pl/page',
        ]);

        $this->postJson('/api/track', [
            'url' => 'https://vify.pl/page',
        ]);

        $this->assertDatabaseCount('urls', 1);
        $this->assertDatabaseCount('page_visits', 1);
    }

    public function test_urls_with_fragment_are_treated_as_same(): void
    {
        $this->postJson('/api/track', [
            'url' => 'https://passhotel.vify.pl/',
        ]);

        $this->postJson('/api/track', [
            'url' => 'https://passhotel.vify.pl/#hotel',
        ]);

        $this->assertDatabaseCount('urls', 1);
        $this->assertDatabaseCount('page_visits', 1);
    }

    public function test_url_is_split_into_base_path_and_path(): void
    {
        $this->postJson('/api/track', [
            'url' => 'https://passhotel.vify.pl/assets/Passhotel_Bistro_Glowne.pdf',
        ]);

        $this->assertDatabaseHas('urls', [
            'base_path' => 'passhotel.vify.pl',
            'url' => '/assets/Passhotel_Bistro_Glowne.pdf',
        ]);
    }

    public function test_url_preserves_query_string(): void
    {
        $this->postJson('/api/track', [
            'url' => 'https://test.vify.pl/page?ref=google&lang=pl',
        ]);

        $this->assertDatabaseHas('urls', [
            'base_path' => 'test.vify.pl',
            'url' => '/page?ref=google&lang=pl',
        ]);
    }
}
