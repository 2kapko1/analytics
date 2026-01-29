<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrackingTest extends TestCase
{
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
}
