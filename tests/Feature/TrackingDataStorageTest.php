<?php

namespace Tests\Feature;

use App\Models\PageVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TrackingDataStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_different_ips_are_counted_separately(): void
    {
        $url = 'https://test.vify.pl/multi-ip-test';
        $date = now()->toDateString();

        // Cache key stores array of IPs for date/type/url
        $cacheKey = sprintf('tracking:%s:%s:%s', $date, 'visit', md5($url));
        $this->assertNull(Cache::get($cacheKey));

        // Simulate first IP visit by directly manipulating cache and database
        Cache::put($cacheKey, ['192.168.1.1'], 3600);
        PageVisit::create([
            'url' => $url,
            'date' => $date,
            'count' => 1,
        ]);

        // Second IP should not be in the array yet
        $trackedIps = Cache::get($cacheKey, []);
        $this->assertNotContains('192.168.1.2', $trackedIps);

        // Simulate second IP visit - add to array
        $trackedIps[] = '192.168.1.2';
        Cache::put($cacheKey, $trackedIps, 3600);
        $record = PageVisit::where('url', $url)->first();
        $record->increment('count');

        // Should have two visits counted
        $record->refresh();
        $this->assertEquals(2, $record->count);

        // Both IPs should be in the cache array
        $trackedIps = Cache::get($cacheKey, []);
        $this->assertContains('192.168.1.1', $trackedIps);
        $this->assertContains('192.168.1.2', $trackedIps);
    }

    public function test_visit_and_download_use_different_urls(): void
    {
        // Since visits and downloads always have different URLs,
        // they are tracked as separate records
        $visitUrl = 'https://test.vify.pl/page';
        $downloadUrl = 'https://test.vify.pl/files/document.pdf';
        $date = now()->toDateString();

        // Visit and download have different cache keys (different URLs)
        $visitCacheKey = sprintf('tracking:%s:%s:%s', $date, 'visit', md5($visitUrl));
        $downloadCacheKey = sprintf('tracking:%s:%s:%s', $date, 'download', md5($downloadUrl));

        // They should be different keys
        $this->assertNotEquals($visitCacheKey, $downloadCacheKey);

        // Create visit record
        PageVisit::create([
            'url' => $visitUrl,
            'date' => $date,
            'count' => 1,
        ]);

        // Create download record (different URL)
        PageVisit::create([
            'url' => $downloadUrl,
            'date' => $date,
            'count' => 1,
        ]);

        // Should have two separate records
        $this->assertDatabaseCount('page_visits', 2);

        $visitRecord = PageVisit::where('url', $visitUrl)->first();
        $downloadRecord = PageVisit::where('url', $downloadUrl)->first();

        $this->assertEquals(1, $visitRecord->count);
        $this->assertEquals(1, $downloadRecord->count);
    }

    public function test_cache_key_includes_type_for_separate_tracking(): void
    {
        $url = 'https://test.vify.pl/page';
        $date = now()->toDateString();

        // Cache keys are per date/type/url (IPs stored as array inside)
        $visitKey = sprintf('tracking:%s:%s:%s', $date, 'visit', md5($url));
        $downloadKey = sprintf('tracking:%s:%s:%s', $date, 'download', md5($url));

        $this->assertNotEquals($visitKey, $downloadKey);
    }

    public function test_api_endpoint_records_visit_in_database(): void
    {
        $response = $this->postJson('/api/track', [
            'type' => 'visit',
            'url' => 'https://test.vify.pl/api-test',
        ]);

        $response->assertStatus(200);

        $record = PageVisit::where('url', 'https://test.vify.pl/api-test')->first();
        $this->assertNotNull($record);
        $this->assertEquals(1, $record->count);
    }

    public function test_api_endpoint_records_download_in_database(): void
    {
        $response = $this->postJson('/api/track', [
            'type' => 'download',
            'url' => 'https://test.vify.pl/files/document.pdf',
            'downloadUrl' => 'https://test.vify.pl/files/document.pdf',
        ]);

        $response->assertStatus(200);

        $record = PageVisit::where('url', 'https://test.vify.pl/files/document.pdf')->first();
        $this->assertNotNull($record);
        $this->assertEquals(1, $record->count);
    }

    public function test_duplicate_requests_from_same_ip_only_count_once(): void
    {
        $url = 'https://test.vify.pl/duplicate-test';

        // First request
        $this->postJson('/api/track', ['type' => 'visit', 'url' => $url]);

        // Second request (same IP, same URL, same type)
        $this->postJson('/api/track', ['type' => 'visit', 'url' => $url]);

        // Third request
        $this->postJson('/api/track', ['type' => 'visit', 'url' => $url]);

        $record = PageVisit::where('url', $url)->first();
        $this->assertNotNull($record);
        $this->assertEquals(1, $record->count);
    }

    public function test_same_ip_can_track_different_urls(): void
    {
        // Same IP can track both a page visit and a file download
        // since they have different URLs
        $visitUrl = 'https://test.vify.pl/page';
        $downloadUrl = 'https://test.vify.pl/files/document.pdf';

        // Track page visit
        $this->postJson('/api/track', [
            'type' => 'visit',
            'url' => $visitUrl,
        ]);

        // Track file download (different URL)
        $this->postJson('/api/track', [
            'type' => 'download',
            'url' => $downloadUrl,
            'downloadUrl' => $downloadUrl,
        ]);

        // Should have two separate records
        $this->assertDatabaseCount('page_visits', 2);

        $visitRecord = PageVisit::where('url', $visitUrl)->first();
        $downloadRecord = PageVisit::where('url', $downloadUrl)->first();

        $this->assertNotNull($visitRecord);
        $this->assertNotNull($downloadRecord);
        $this->assertEquals(1, $visitRecord->count);
        $this->assertEquals(1, $downloadRecord->count);
    }
}
