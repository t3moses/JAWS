<?php

declare(strict_types=1);

namespace Tests\Integration\Api;

use PHPUnit\Framework\TestCase;

/**
 * API tests for User Profile endpoints
 */
class UserProfileApiTest extends TestCase
{
    use ApiTestTrait;

    private string $baseUrl = 'http://localhost:8000/api';

    public function testGetCrewProfile(): void
    {
        $testData = $this->createTestCrew($this->baseUrl);

        $response = $this->makeRequest('GET', "{$this->baseUrl}/users/me", null, [
            "Authorization: Bearer {$testData['token']}",
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('data', $response['body']);

        $data = $response['body']['data'];

        // Verify crew profile is present
        $this->assertArrayHasKey('crewProfile', $data);

        $crew = $data['crewProfile'];

        // Verify crew details match registration
        $this->assertEquals($testData['firstName'], $crew['firstName']);
        $this->assertEquals($testData['lastName'], $crew['lastName']);

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }

    public function testGetBoatOwnerProfile(): void
    {
        $testData = $this->createTestBoatOwner($this->baseUrl);

        $response = $this->makeRequest('GET', "{$this->baseUrl}/users/me", null, [
            "Authorization: Bearer {$testData['token']}",
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('data', $response['body']);

        $data = $response['body']['data'];

        // Verify boat profile is present
        $this->assertArrayHasKey('boatProfile', $data);

        $boat = $data['boatProfile'];

        // Verify boat details match registration
        $this->assertEquals($testData['ownerFirstName'], $boat['ownerFirstName']);
        $this->assertEquals($testData['ownerLastName'], $boat['ownerLastName']);

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }

    public function testUpdateEmailOnly(): void
    {
        $testData = $this->createTestCrew($this->baseUrl);
        $newEmail = $this->makeUniqueEmail('updated.email');

        $response = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me", [
            'email' => $newEmail,
        ], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('profile', $response['body']['data']);

        $profile = $response['body']['data']['profile'];

        // Verify email was updated
        $this->assertEquals($newEmail, $profile['user']['email']);

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }

    public function testUpdatePasswordOnly(): void
    {
        $testData = $this->createTestCrew($this->baseUrl);

        $response = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me", [
            'password' => 'NewSecurePass123!',
        ], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('profile', $response['body']['data']);

        // Password is hashed, can't verify directly, but success indicates it worked

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }

    public function testUpdateCrewProfile(): void
    {
        $testData = $this->createTestCrew($this->baseUrl);
        $suffix = $this->makeUniqueSuffix();

        $response = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me", [
            'crewProfile' => [
                'displayName' => "Updated Name {$suffix}",
                'mobile' => '555-9999',
                'skill' => 2,
                'socialPreference' => false,
                'membershipNumber' => 'NSC99999',
                'experience' => 'Updated experience text',
            ],
        ], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('crewProfile', $response['body']['data']['profile']);

        $crew = $response['body']['data']['profile']['crewProfile'];

        // Verify crew profile fields were updated
        $this->assertEquals("Updated Name {$suffix}", $crew['displayName']);
        $this->assertEquals('555-9999', $crew['mobile']);
        $this->assertEquals(2, $crew['skill']);
        $this->assertFalse($crew['socialPreference']);
        $this->assertEquals('NSC99999', $crew['membershipNumber']);
        $this->assertEquals('Updated experience text', $crew['experience']);

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }

    public function testUpdateBoatProfile(): void
    {
        $testData = $this->createTestBoatOwner($this->baseUrl);
        $suffix = $this->makeUniqueSuffix();

        $response = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me", [
            'boatProfile' => [
                'displayName' => "Updated Boat Name {$suffix}",
                'ownerMobile' => '555-8888',
                'minBerths' => 3,
                'maxBerths' => 6,
                'assistanceRequired' => true,
                'socialPreference' => false,
            ],
        ], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('boatProfile', $response['body']['data']['profile']);

        $boat = $response['body']['data']['profile']['boatProfile'];

        // Verify boat profile fields were updated
        $this->assertEquals("Updated Boat Name {$suffix}", $boat['displayName']);
        $this->assertEquals('555-8888', $boat['ownerMobile']);
        $this->assertEquals(3, $boat['minBerths']);
        $this->assertEquals(6, $boat['maxBerths']);
        $this->assertTrue($boat['assistanceRequired']);
        $this->assertFalse($boat['socialPreference']);

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }

    public function testUpdateMultipleFields(): void
    {
        $testData = $this->createTestCrew($this->baseUrl);
        $newEmail = $this->makeUniqueEmail('updated.multi');
        $suffix = $this->makeUniqueSuffix();

        $response = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me", [
            'email' => $newEmail,
            'crewProfile' => [
                'displayName' => "Multi Update {$suffix}",
                'skill' => 2,
            ],
        ], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('profile', $response['body']['data']);

        $profile = $response['body']['data']['profile'];

        // Verify email was updated
        $this->assertEquals($newEmail, $profile['user']['email']);

        // Verify crew profile was updated
        $this->assertEquals("Multi Update {$suffix}", $profile['crewProfile']['displayName']);
        $this->assertEquals(2, $profile['crewProfile']['skill']);

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }

    public function testValidationErrors(): void
    {
        $testData = $this->createTestCrew($this->baseUrl);

        // Test 1: Invalid email format
        $response1 = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me", [
            'email' => 'invalid-email',
        ], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        $this->assertEquals(400, $response1['status']);
        $this->assertArrayHasKey('error', $response1['body']);

        // Test 2: Password too short
        $response2 = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me", [
            'password' => 'short',
        ], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        $this->assertEquals(400, $response2['status']);
        $this->assertArrayHasKey('error', $response2['body']);

        // Test 3: Empty request body (no updates)
        $response3 = $this->makeRequest('PATCH', "{$this->baseUrl}/users/me", [], [
            "Authorization: Bearer {$testData['token']}",
        ]);

        $this->assertEquals(400, $response3['status']);
        $this->assertArrayHasKey('error', $response3['body']);

        // Cleanup
        $this->cleanupTestUser($testData['userId']);
    }
}
