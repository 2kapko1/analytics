<?php

namespace Tests\Feature;

use App\Models\PageVisit;
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
            'type' => 'visit',
            'url' => 'https://test.vify.pl/page',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tracking data received',
            ]);
    }

    public function test_track_endpoint_accepts_download_event(): void
    {
        $response = $this->postJson('/api/track', [
            'type' => 'download',
            'url' => 'https://test.vify.pl/page',
            'downloadUrl' => 'https://test.vify.pl/files/document.pdf',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tracking data received',
            ]);
    }

    public function test_track_endpoint_requires_type(): void
    {
        $response = $this->postJson('/api/track', [
            'url' => 'https://test.vify.pl/page',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_track_endpoint_requires_url(): void
    {
        $response = $this->postJson('/api/track', [
            'type' => 'visit',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_track_endpoint_validates_type_values(): void
    {
        $response = $this->postJson('/api/track', [
            'type' => 'invalid',
            'url' => 'https://test.vify.pl/page',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_track_endpoint_validates_url_format(): void
    {
        $response = $this->postJson('/api/track', [
            'type' => 'visit',
            'url' => 'not-a-valid-url',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_track_endpoint_validates_download_url_format(): void
    {
        $response = $this->postJson('/api/track', [
            'type' => 'download',
            'url' => 'https://test.vify.pl/page',
            'downloadUrl' => 'not-a-valid-url',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['downloadUrl']);
    }

    public function test_visit_creates_database_record(): void
    {
        $this->postJson('/api/track', [
            'type' => 'visit',
            'url' => 'https://test.vify.pl/page',
        ]);

        $record = PageVisit::where('url', 'https://test.vify.pl/page')->first();
        $this->assertNotNull($record);
        $this->assertEquals(now()->toDateString(), $record->date->toDateString());
        $this->assertEquals(1, $record->count);
    }

    public function test_download_creates_database_record(): void
    {
        $this->postJson('/api/track', [
            'type' => 'download',
            'url' => 'https://test.vify.pl/files/doc.pdf',
            'downloadUrl' => 'https://test.vify.pl/files/doc.pdf',
        ]);

        $record = PageVisit::where('url', 'https://test.vify.pl/files/doc.pdf')->first();
        $this->assertNotNull($record);
        $this->assertEquals(now()->toDateString(), $record->date->toDateString());
        $this->assertEquals(1, $record->count);
    }

    public function test_multiple_requests_from_same_ip_only_count_once_per_day(): void
    {
        $url = 'https://test.vify.pl/unique-test';

        // Send multiple requests from the same IP
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/track', [
                'type' => 'visit',
                'url' => $url,
            ]);
        }

        // Should only have one visit counted
        $record = PageVisit::where('url', $url)->first();
        $this->assertNotNull($record);
        $this->assertEquals(1, $record->count);

        // Should only have one record
        $this->assertDatabaseCount('page_visits', 1);
    }

    public function test_different_urls_are_tracked_separately(): void
    {
        $this->postJson('/api/track', [
            'type' => 'visit',
            'url' => 'https://test.vify.pl/page1',
        ]);

        $this->postJson('/api/track', [
            'type' => 'visit',
            'url' => 'https://test.vify.pl/page2',
        ]);

        $this->assertDatabaseCount('page_visits', 2);

        $this->assertDatabaseHas('page_visits', [
            'url' => 'https://test.vify.pl/page1',
            'count' => 1,
        ]);

        $this->assertDatabaseHas('page_visits', [
            'url' => 'https://test.vify.pl/page2',
            'count' => 1,
        ]);
    }

    public function test_ip_addresses_are_not_stored_in_database(): void
    {
        $this->postJson('/api/track', [
            'type' => 'visit',
            'url' => 'https://test.vify.pl/privacy-test',
        ]);

        // Verify the page_visits table structure doesn't have IP column
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('page_visits');
        $this->assertNotContains('ip', $columns);
        $this->assertNotContains('ip_address', $columns);

        // Verify the record doesn't contain IP data
        $record = PageVisit::first();
        $this->assertNotNull($record);
        $attributes = $record->getAttributes();
        $this->assertArrayNotHasKey('ip', $attributes);
        $this->assertArrayNotHasKey('ip_address', $attributes);
    }

    public function test_rejects_non_vify_pl_domains(): void
    {
        $response = $this->postJson('/api/track', [
            'type' => 'visit',
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
            'type' => 'visit',
            'url' => 'https://vify.pl/page',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('page_visits', 1);
    }

    public function test_accepts_any_vify_pl_subdomain(): void
    {
        $this->postJson('/api/track', [
            'type' => 'visit',
            'url' => 'https://app.vify.pl/page',
        ])->assertStatus(200);

        $this->postJson('/api/track', [
            'type' => 'visit',
            'url' => 'https://deep.sub.vify.pl/page',
        ])->assertStatus(200);

        $this->assertDatabaseCount('page_visits', 2);
    }

    public function test_rejects_domains_ending_with_vify_pl_but_not_subdomain(): void
    {
        $response = $this->postJson('/api/track', [
            'type' => 'visit',
            'url' => 'https://notvify.pl/page',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('page_visits', 0);
    }
}
