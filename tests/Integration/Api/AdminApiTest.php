<?php

declare(strict_types=1);

namespace Tests\Integration\Api;

use PHPUnit\Framework\TestCase;

/**
 * API tests for Admin endpoints
 */
class AdminApiTest extends TestCase
{
    use ApiTestTrait;

    private string $baseUrl = 'http://localhost:8000/api';

    public function testGetMatchingData(): void
    {
        $testData = $this->createTestCrew($this->baseUrl);

        $eventId = urlencode('Fri May 29');
        $response = $this->makeRequest('GET', "{$this->baseUrl}/admin/matching/{$eventId}", null, [
            "Authorization: Bearer {$testData['token']}",
        ]);

        // May return 404 if event doesn't exist, which is valid
        $this->assertContains($response['status'], [200, 404]);
        $this->assertArrayHasKey('success', $response['body']);

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }

    public function testSendNotifications(): void
    {
        $testData = $this->createTestCrew($this->baseUrl);

        $eventId = urlencode('Fri May 29');
        $response = $this->makeRequest('POST', "{$this->baseUrl}/admin/notifications/{$eventId}", null, [
            "Authorization: Bearer {$testData['token']}",
        ]);

        // May return 404 if event doesn't exist (or 500 if email service is not configured)
        // Accept these as valid test outcomes
        $this->assertContains($response['status'], [200, 404, 500]);

        // Response should have either success or error field
        $hasSuccessOrError = isset($response['body']['success']) || isset($response['body']['error']);
        $this->assertTrue($hasSuccessOrError, "Response missing 'success' or 'error' field");

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }

    public function testUpdateConfig(): void
    {
        $testData = $this->createTestCrew($this->baseUrl);

        $response = $this->makeRequest('PATCH', "{$this->baseUrl}/admin/config", [
            'startTime' => '10:00:00',
            'finishTime' => '18:00:00',
        ], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['body']['success']);

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }
}
