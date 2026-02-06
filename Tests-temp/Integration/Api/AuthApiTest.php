<?php

declare(strict_types=1);

namespace Tests\Integration\Api;

use PHPUnit\Framework\TestCase;

/**
 * API tests for Authentication endpoints
 */
class AuthApiTest extends TestCase
{
    use ApiTestTrait;

    private string $baseUrl = 'http://localhost:8000/api';

    public function testRegisterCrewAccount(): void
    {
        $suffix = $this->makeUniqueSuffix();
        $firstName = "John{$suffix}";
        $lastName = "Doe{$suffix}";

        $response = $this->makeRequest('POST', "{$this->baseUrl}/auth/register", [
            'email' => $this->makeUniqueEmail('john.doe.crew'),
            'password' => 'TestPass123',
            'accountType' => 'crew',
            'profile' => [
                'displayName' => "John Doe {$suffix}",
                'firstName' => $firstName,
                'lastName' => $lastName,
                'skill' => 1,
            ],
        ]);

        $this->assertEquals(201, $response['status']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('token', $response['body']['data']);
        $this->assertArrayHasKey('user', $response['body']['data']);

        // Cleanup
        $userId = $response['body']['data']['user']['id'] ?? null;
        $this->cleanupTestUser($userId);
    }

    public function testRegisterBoatOwnerAccount(): void
    {
        $suffix = $this->makeUniqueSuffix();
        $ownerFirstName = "TestBoat{$suffix}";
        $ownerLastName = "Owner";

        $response = $this->makeRequest('POST', "{$this->baseUrl}/auth/register", [
            'email' => $this->makeUniqueEmail('john.doe.boat'),
            'password' => 'TestPass123',
            'accountType' => 'boat_owner',
            'profile' => [
                'displayName' => "Test Boat {$suffix}",
                'ownerFirstName' => $ownerFirstName,
                'ownerLastName' => $ownerLastName,
                'ownerMobile' => '555-1234',
                'minBerths' => 2,
                'maxBerths' => 4,
                'assistanceRequired' => false,
                'socialPreference' => true,
            ],
        ]);

        $this->assertEquals(201, $response['status']);
        $this->assertTrue($response['body']['success']);
        $this->assertArrayHasKey('token', $response['body']['data']);
        $this->assertArrayHasKey('user', $response['body']['data']);

        // Cleanup
        $userId = $response['body']['data']['user']['id'] ?? null;
        $this->cleanupTestUser($userId);
    }

    public function testAuthenticationFailureWithoutToken(): void
    {
        $response = $this->makeRequest('GET', "{$this->baseUrl}/assignments");

        $this->assertEquals(401, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
    }
}
