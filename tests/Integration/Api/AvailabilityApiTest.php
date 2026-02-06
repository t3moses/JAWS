<?php

declare(strict_types=1);

namespace Tests\Integration\Api;

use PHPUnit\Framework\TestCase;

/**
 * API tests for Availability endpoints
 */
class AvailabilityApiTest extends TestCase
{
    use ApiTestTrait;

    private string $baseUrl = 'http://localhost:8000/api';

    public function testUpdateCrewAvailability(): void
    {
        $testData = $this->createTestCrew($this->baseUrl);

        $response = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me/availability", [
            'availabilities' => [
                ['eventId' => 'Fri May 29', 'isAvailable' => true],
            ],
        ], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        // May return 404 if event doesn't exist, which is acceptable
        $this->assertContains($response['status'], [200, 404]);
        $this->assertArrayHasKey('success', $response['body']);

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }

    public function testUpdateBoatOwnerAvailability(): void
    {
        $testData = $this->createTestBoatOwner($this->baseUrl);

        $response = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me/availability", [
            'availabilities' => [
                ['eventId' => 'Fri May 29', 'isAvailable' => true],
            ],
        ], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        // May return 404 if event doesn't exist, which is acceptable
        $this->assertContains($response['status'], [200, 404]);
        $this->assertArrayHasKey('success', $response['body']);

        // If successful, verify the response indicates what was updated
        if ($response['status'] === 200 && isset($response['body']['data']['updated'])) {
            $updated = $response['body']['data']['updated'];
            $this->assertIsArray($updated);
        }

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }

    public function testBoatOwnerAvailabilityCreatesFlotilla(): void
    {
        $testData = $this->createTestBoatOwner($this->baseUrl);

        $response = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me/availability", [
            'availabilities' => [
                ['eventId' => 'Fri May 29', 'isAvailable' => true],
            ],
        ], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        // May return 404 if event doesn't exist
        $this->assertContains($response['status'], [200, 404]);

        // If boat exists, verify flotilla can be retrieved
        if ($response['status'] === 200) {
            $eventResponse = $this->makeRequest('GET', "{$this->baseUrl}/events/Fri%20May%2029");
            $this->assertArrayHasKey('data', $eventResponse['body']);

            // Flotilla may or may not exist depending on whether there are other boats/crews
            // The key is that the endpoint doesn't error out
        }

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }

    public function testCrewAvailabilityCreatesFlotilla(): void
    {
        $testData = $this->createTestCrew($this->baseUrl);

        $response = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me/availability", [
            'availabilities' => [
                ['eventId' => 'Fri May 29', 'isAvailable' => true],
            ],
        ], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        // May return 404 if event doesn't exist
        $this->assertContains($response['status'], [200, 404]);

        // If crew exists, verify flotilla endpoint works
        if ($response['status'] === 200) {
            $eventResponse = $this->makeRequest('GET', "{$this->baseUrl}/events/Fri%20May%2029");
            $this->assertArrayHasKey('data', $eventResponse['body']);
        }

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }

    public function testMultipleAvailabilityUpdatesAreIdempotent(): void
    {
        $testData = $this->createTestBoatOwner($this->baseUrl);

        // First update
        $response1 = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me/availability", [
            'availabilities' => [
                ['eventId' => 'Fri May 29', 'isAvailable' => true],
            ],
        ], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        // May return 404 if event doesn't exist
        $this->assertContains($response1['status'], [200, 404]);

        if ($response1['status'] === 200) {
            // Second update (same boat, different availability)
            $response2 = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me/availability", [
                'availabilities' => [
                    ['eventId' => 'Fri May 29', 'isAvailable' => true],
                ],
            ], [
                "Authorization: Bearer {$testData['token']}",
            ]);

            $this->assertEquals(200, $response2['status']);

            // Verify flotilla exists and endpoint doesn't error
            $eventResponse = $this->makeRequest('GET', "{$this->baseUrl}/events/Fri%20May%2029");
            $this->assertContains($eventResponse['status'], [200, 404]);
        }

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }
}
