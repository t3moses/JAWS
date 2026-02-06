<?php

declare(strict_types=1);

namespace Tests\Integration\Api;

use PHPUnit\Framework\TestCase;

/**
 * API tests for Assignment endpoints
 */
class AssignmentApiTest extends TestCase
{
    use ApiTestTrait;

    private string $baseUrl = 'http://localhost:8000/api';

    public function testGetUserAssignments(): void
    {
        $testData = $this->createTestCrew($this->baseUrl);

        $response = $this->makeRequest('GET', "{$this->baseUrl}/assignments", null, [
            "Authorization: Bearer {$testData['token']}",
        ]);

        // May return 404 if no assignments exist, which is acceptable
        $this->assertContains($response['status'], [200, 404]);
        $this->assertArrayHasKey('success', $response['body']);

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }
}
