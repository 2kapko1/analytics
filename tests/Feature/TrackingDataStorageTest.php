<?php

namespace Tests\Feature;

use App\Models\PageVisit;
use App\Models\Url;
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

    public function test_different_ips_are_counted_separately(): void
    {
        $fullUrl = 'https://test.vify.pl/multi-ip-test';
        $date = now()->toDateString();

        $parsed = Url::parseUrl($fullUrl);
        $url = Url::firstOrCreate([
            'base_path' => $parsed['base_path'],
            'url' => $parsed['url'],
        ]);

        $cacheKey = sprintf('tracking:%s:%s', $date, $url->id);
        $this->assertNull(Cache::get($cacheKey));

        Cache::put($cacheKey, ['192.168.1.1'], 3600);
        PageVisit::create([
            'url_id' => $url->id,
            'date' => $date,
            'visits' => 1,
            'unique_visits' => 1,
        ]);

        $trackedIps = Cache::get($cacheKey, []);
        $this->assertNotContains('192.168.1.2', $trackedIps);

        $trackedIps[] = '192.168.1.2';
        Cache::put($cacheKey, $trackedIps, 3600);
        $record = PageVisit::where('url_id', $url->id)->first();
        $record->increment('unique_visits');

        $record->refresh();
        $this->assertEquals(2, $record->unique_visits);

        $trackedIps = Cache::get($cacheKey, []);
        $this->assertContains('192.168.1.1', $trackedIps);
        $this->assertContains('192.168.1.2', $trackedIps);
    }

    public function test_visit_and_download_use_different_urls(): void
    {
        $date = now()->toDateString();

        $this->createUrlAndVisit('https://test.vify.pl/page', $date);
        $this->createUrlAndVisit('https://test.vify.pl/files/document.pdf', $date);

        $this->assertDatabaseCount('page_visits', 2);
    }

    public function test_api_endpoint_records_visit_in_database(): void
    {
        $response = $this->postJson('/api/track', [
            'url' => 'https://test.vify.pl/api-test',
        ]);

        $response->assertStatus(200);

        $url = Url::where('base_path', 'test.vify.pl')->where('url', '/api-test')->first();
        $this->assertNotNull($url);

        $record = PageVisit::where('url_id', $url->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals(1, $record->unique_visits);
    }

    public function test_api_endpoint_records_download_in_database(): void
    {
        $response = $this->postJson('/api/track', [
            'url' => 'https://test.vify.pl/files/document.pdf',
        ]);

        $response->assertStatus(200);

        $url = Url::where('base_path', 'test.vify.pl')->where('url', '/files/document.pdf')->first();
        $this->assertNotNull($url);

        $record = PageVisit::where('url_id', $url->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals(1, $record->unique_visits);
    }

    public function test_duplicate_requests_from_same_ip_only_count_once(): void
    {
        $url = 'https://test.vify.pl/duplicate-test';

        $this->postJson('/api/track', ['url' => $url]);
        $this->postJson('/api/track', ['url' => $url]);
        $this->postJson('/api/track', ['url' => $url]);

        $urlRecord = Url::where('base_path', 'test.vify.pl')->where('url', '/duplicate-test')->first();
        $record = PageVisit::where('url_id', $urlRecord->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals(1, $record->unique_visits);
        $this->assertEquals(3, $record->visits);
    }

    public function test_same_ip_can_track_different_urls(): void
    {
        $this->postJson('/api/track', [
            'url' => 'https://test.vify.pl/page',
        ]);

        $this->postJson('/api/track', [
            'url' => 'https://test.vify.pl/files/document.pdf',
        ]);

        $this->assertDatabaseCount('page_visits', 2);
    }
}
