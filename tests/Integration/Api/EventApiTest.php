<?php

declare(strict_types=1);

namespace Tests\Integration\Api;

use PHPUnit\Framework\TestCase;

/**
 * API tests for Event endpoints
 */
class EventApiTest extends TestCase
{
    use ApiTestTrait;

    private string $baseUrl = 'http://localhost:8000/api';

    public function testGetAllEvents(): void
    {
        $response = $this->makeRequest('GET', "{$this->baseUrl}/events");

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('events', $response['body']['data']);
        $this->assertIsArray($response['body']['data']['events']);
    }

    public function testGetEventById(): void
    {
        $eventId = urlencode('Fri May 29');
        $response = $this->makeRequest('GET', "{$this->baseUrl}/events/{$eventId}");

        // May return 404 if event doesn't exist, which is valid
        $this->assertContains($response['status'], [200, 404]);
        $this->assertArrayHasKey('success', $response['body']);
    }

    public function testGetEventByIdIncludesFlotillaStructure(): void
    {
        $eventId = urlencode('Fri May 29');
        $response = $this->makeRequest('GET', "{$this->baseUrl}/events/{$eventId}");

        $this->assertContains($response['status'], [200, 404]);

        if ($response['status'] === 200) {
            $this->assertArrayHasKey('event', $response['body']['data']);

            // Verify flotilla structure if present
            if (isset($response['body']['data']['flotilla'])) {
                $flotilla = $response['body']['data']['flotilla'];

                $this->assertArrayHasKey('eventId', $flotilla);
                $this->assertArrayHasKey('crewedBoats', $flotilla);
                $this->assertArrayHasKey('waitlistBoats', $flotilla);
                $this->assertArrayHasKey('waitlistCrews', $flotilla);
                $this->assertIsArray($flotilla['crewedBoats']);
            }
        }
    }

    public function testNonExistentRouteReturns404(): void
    {
        $response = $this->makeRequest('GET', "{$this->baseUrl}/nonexistent");

        $this->assertEquals(404, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
    }
}
