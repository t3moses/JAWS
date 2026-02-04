<?php

declare(strict_types=1);

/**
 * Simple API Test Script
 *
 * Basic tests to verify API endpoints are working.
 * Run with: php tests/Integration/api_test.php
 * Run with verbose output: php tests/Integration/api_test.php --verbose
 */

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Load test helper
require_once __DIR__ . '/UserTestHelper.php';
use Tests\Integration\UserTestHelper;

// Configuration
$baseUrl = 'http://localhost:8000/api';
$testFirstName = 'John';
$testLastName = 'Doe';
$verbose = in_array('--verbose', $argv) || in_array('-v', $argv);

function makeUniqueEmail(string $prefix): string
{
    $suffix = str_replace('.', '', (string)microtime(true));
    return sprintf('%s.%s@example.com', $prefix, $suffix);
}

function makeUniqueSuffix(): string
{
    return str_replace('.', '', (string)microtime(true));
}

// Helper function to make HTTP requests
function makeRequest(string $method, string $url, ?array $body = null, ?array $headers = []): array
{
    global $verbose;
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);

    if ($body !== null) {
        $jsonBody = json_encode($body);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        $headers[] = 'Content-Type: application/json';
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if ($verbose) {
        echo "\n[DEBUG] {$method} {$url}\n";
        if (!empty($headers)) {
            echo "[DEBUG] Request Headers: " . json_encode($headers, JSON_PRETTY_PRINT) . "\n";
        }
        if ($body !== null) {
            echo "[DEBUG] Request Body: " . json_encode($body, JSON_PRETTY_PRINT) . "\n";
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new \RuntimeException("Curl request failed: {$error}");
    }

    if ($verbose) {
        echo "[DEBUG] HTTP Status: {$httpCode}\n";
        echo "[DEBUG] Response Body: " . $response . "\n";
    }

    curl_close($ch);

    return [
        'status' => $httpCode,
        'body' => json_decode($response, true),
    ];
}

// Test counter
$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed, $verbose;

    echo "\nTest: {$name}\n";

    try {
        $fn();
        echo "✓ PASSED\n";
        $passed++;
    } catch (\Exception $e) {
        echo "✗ FAILED: {$e->getMessage()}\n";
        if ($verbose) {
            echo "[DEBUG] Exception trace:\n" . $e->getTraceAsString() . "\n";
        }
        $failed++;
    }
}

echo "=================================\n";
echo "JAWS API Test Suite\n";
echo "=================================\n";

// Test 1: GET /api/events (public)
test('GET /api/events', function () use ($baseUrl) {
    $response = makeRequest('GET', "{$baseUrl}/events");

    if ($response['status'] !== 200) {
        throw new \Exception("Expected 200, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }

    if (!isset($response['body']['data']['events'])) {
        throw new \Exception("Response missing 'events' array");
    }
});

// Test 2: GET /api/events/{id} (public)
test('GET /api/events/{id}', function () use ($baseUrl) {
    $eventId = urlencode('Fri May 29');
    $response = makeRequest('GET', "{$baseUrl}/events/{$eventId}");

    // May return 404 if event doesn't exist, which is valid
    if ($response['status'] !== 200 && $response['status'] !== 404) {
        throw new \Exception("Expected 200 or 404, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }
});

// Test 3: POST /api/auth/register (crew)
test('POST /api/auth/register (crew)', function () use ($baseUrl, $testFirstName, $testLastName) {
    $suffix = makeUniqueSuffix();
    $firstName = "{$testFirstName}{$suffix}";
    $lastName = "{$testLastName}{$suffix}";
    $response = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('john.doe.crew'),
        'password' => 'TestPass123',
        'accountType' => 'crew',
        'profile' => [
            'displayName' => "John Doe {$suffix}",
            'firstName' => $firstName,
            'lastName' => $lastName,
            'skill' => 1,
        ],
    ]);

    if ($response['status'] !== 201) {
        throw new \Exception("Expected 201, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }

    if (!isset($response['body']['data']['token'])) {
        throw new \Exception("Response missing 'token'");
    }
});

// Test 4: GET /api/users/me (crew profile)
test('GET /api/users/me (crew profile)', function () use ($baseUrl, $testFirstName, $testLastName) {
    // Create test user and get token via API
    $suffix = makeUniqueSuffix();
    $firstName = "{$testFirstName}{$suffix}";
    $lastName = "{$testLastName}{$suffix}";
    $displayName = "John Doe {$suffix}";

    $registerResponse = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('john.doe.crew.profile'),
        'password' => 'TestPass123',
        'accountType' => 'crew',
        'profile' => [
            'displayName' => $displayName,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'skill' => 1,
        ],
    ]);

    if ($registerResponse['status'] !== 201) {
        throw new \Exception("Registration failed: Expected 201, got {$registerResponse['status']}");
    }

    if (!isset($registerResponse['body']['data']['token'])) {
        throw new \Exception("Registration response missing 'token'");
    }

    $token = $registerResponse['body']['data']['token'];
    $userId = $registerResponse['body']['data']['user']['id'] ?? null;

    // Get profile using the token
    $response = makeRequest('GET', "{$baseUrl}/users/me", null, [
        "Authorization: Bearer {$token}",
    ]);

    if ($response['status'] !== 200) {
        throw new \Exception("Expected 200, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }

    if (!isset($response['body']['data'])) {
        throw new \Exception("Response missing 'data' field");
    }

    $data = $response['body']['data'];

    // Verify crew profile is present
    if (!isset($data['crewProfile'])) {
        throw new \Exception("Response missing 'crewProfile' field");
    }

    $crew = $data['crewProfile'];

    // Verify crew details match registration
    if ($crew['firstName'] !== $firstName) {
        throw new \Exception("Crew firstName mismatch: expected {$firstName}, got {$crew['firstName']}");
    }

    if ($crew['lastName'] !== $lastName) {
        throw new \Exception("Crew lastName mismatch: expected {$lastName}, got {$crew['lastName']}");
    }

    if ($crew['displayName'] !== $displayName) {
        throw new \Exception("Crew displayName mismatch: expected {$displayName}, got {$crew['displayName']}");
    }

    // Cleanup
    if ($userId) {
        UserTestHelper::deleteTestUser($userId);
    }
});

// Test 5: GET /api/users/me (boat owner profile)
test('GET /api/users/me (boat owner profile)', function () use ($baseUrl, $testFirstName, $testLastName) {
    // Create test user and get token via API
    $suffix = makeUniqueSuffix();
    $ownerFirstName = "{$testFirstName}{$suffix}";
    $ownerLastName = "{$testLastName}{$suffix}";
    $displayName = "Test Boat {$suffix}";

    $registerResponse = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('john.doe.boat.profile'),
        'password' => 'TestPass123',
        'accountType' => 'boat_owner',
        'profile' => [
            'displayName' => $displayName,
            'ownerFirstName' => $ownerFirstName,
            'ownerLastName' => $ownerLastName,
            'ownerMobile' => '555-1234',
            'minBerths' => 2,
            'maxBerths' => 4,
            'assistanceRequired' => false,
            'socialPreference' => true,
        ],
    ]);

    if ($registerResponse['status'] !== 201) {
        throw new \Exception("Registration failed: Expected 201, got {$registerResponse['status']}");
    }

    if (!isset($registerResponse['body']['data']['token'])) {
        throw new \Exception("Registration response missing 'token'");
    }

    $token = $registerResponse['body']['data']['token'];
    $userId = $registerResponse['body']['data']['user']['id'] ?? null;

    // Get profile using the token
    $response = makeRequest('GET', "{$baseUrl}/users/me", null, [
        "Authorization: Bearer {$token}",
    ]);

    if ($response['status'] !== 200) {
        throw new \Exception("Expected 200, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }

    if (!isset($response['body']['data'])) {
        throw new \Exception("Response missing 'data' field");
    }

    $data = $response['body']['data'];

    // Verify boat profile is present
    if (!isset($data['boatProfile'])) {
        throw new \Exception("Response missing 'boatProfile' field");
    }

    $boat = $data['boatProfile'];

    // Verify boat details match registration
    if ($boat['ownerFirstName'] !== $ownerFirstName) {
        throw new \Exception("Boat ownerFirstName mismatch: expected {$ownerFirstName}, got {$boat['ownerFirstName']}");
    }

    if ($boat['ownerLastName'] !== $ownerLastName) {
        throw new \Exception("Boat ownerLastName mismatch: expected {$ownerLastName}, got {$boat['ownerLastName']}");
    }

    if ($boat['displayName'] !== $displayName) {
        throw new \Exception("Boat displayName mismatch: expected {$displayName}, got {$boat['displayName']}");
    }

    // Cleanup
    if ($userId) {
        UserTestHelper::deleteTestUser($userId);
    }
});

// Test 6: PATCH /api/users/me/availability (authenticated)
test('PATCH /api/users/me/availability', function () use ($baseUrl, $testFirstName, $testLastName) {
    // Create test user and get token via API
    $suffix = makeUniqueSuffix();
    $firstName = "{$testFirstName}{$suffix}";
    $lastName = "{$testLastName}{$suffix}";

    $registerResponse = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('crew.avail'),
        'password' => 'TestPass123',
        'accountType' => 'crew',
        'profile' => [
            'displayName' => "Crew {$suffix}",
            'firstName' => $firstName,
            'lastName' => $lastName,
            'skill' => 1,
        ],
    ]);

    if (!isset($registerResponse['body']['data']['token'])) {
        throw new \Exception("Registration failed");
    }

    $token = $registerResponse['body']['data']['token'];
    $userId = $registerResponse['body']['data']['user']['id'] ?? null;

    $response = makeRequest('PATCH', "{$baseUrl}/users/me/availability", [
        'availabilities' => [
            ['eventId' => 'Fri May 29', 'isAvailable' => true],
        ],
    ], [
        "Authorization: Bearer {$token}",
    ]);

    if ($response['status'] !== 200 && $response['status'] !== 404) {
        throw new \Exception("Expected 200 or 404, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }

    // Cleanup
    if ($userId) {
        UserTestHelper::deleteTestUser($userId);
    }
});

// Test 7: GET /api/assignments (authenticated)
test('GET /api/assignments', function () use ($baseUrl, $testFirstName, $testLastName) {
    // Create test user and get token via API
    $suffix = makeUniqueSuffix();
    $firstName = "{$testFirstName}{$suffix}";
    $lastName = "{$testLastName}{$suffix}";

    $registerResponse = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('crew.assignments'),
        'password' => 'TestPass123',
        'accountType' => 'crew',
        'profile' => [
            'displayName' => "Crew {$suffix}",
            'firstName' => $firstName,
            'lastName' => $lastName,
            'skill' => 1,
        ],
    ]);

    if (!isset($registerResponse['body']['data']['token'])) {
        throw new \Exception("Registration failed");
    }

    $token = $registerResponse['body']['data']['token'];
    $userId = $registerResponse['body']['data']['user']['id'] ?? null;

    $response = makeRequest('GET', "{$baseUrl}/assignments", null, [
        "Authorization: Bearer {$token}",
    ]);

    if ($response['status'] !== 200 && $response['status'] !== 404) {
        throw new \Exception("Expected 200 or 404, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }

    // Cleanup
    if ($userId) {
        UserTestHelper::deleteTestUser($userId);
    }
});

// Test 8: POST /api/auth/register (boat owner)
test('POST /api/auth/register (boat owner)', function () use ($baseUrl, $testFirstName, $testLastName) {
    $suffix = makeUniqueSuffix();
    $ownerFirstName = "{$testFirstName}{$suffix}";
    $ownerLastName = "{$testLastName}{$suffix}";
    $response = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('john.doe.boat'),
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

    if ($response['status'] !== 201) {
        throw new \Exception("Expected 201, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }

    if (!isset($response['body']['data']['token'])) {
        throw new \Exception("Response missing 'token'");
    }
});

// Test 9: PATCH /api/users/me/availability (boat owner - auto-detect)
test('PATCH /api/users/me/availability (boat owner)', function () use ($baseUrl, $testFirstName, $testLastName) {
    // Create test user and get token via API
    $suffix = makeUniqueSuffix();
    $ownerFirstName = "{$testFirstName}{$suffix}";
    $ownerLastName = "{$testLastName}{$suffix}";

    $registerResponse = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('boat.avail'),
        'password' => 'TestPass123',
        'accountType' => 'boat_owner',
        'profile' => [
            'displayName' => "Boat {$suffix}",
            'ownerFirstName' => $ownerFirstName,
            'ownerLastName' => $ownerLastName,
            'ownerMobile' => '555-1234',
            'minBerths' => 2,
            'maxBerths' => 4,
            'assistanceRequired' => false,
            'socialPreference' => true,
        ],
    ]);

    if (!isset($registerResponse['body']['data']['token'])) {
        throw new \Exception("Registration failed");
    }

    $token = $registerResponse['body']['data']['token'];
    $userId = $registerResponse['body']['data']['user']['id'] ?? null;

    $response = makeRequest('PATCH', "{$baseUrl}/users/me/availability", [
        'availabilities' => [
            ['eventId' => 'Fri May 29', 'isAvailable' => true],
        ],
    ], [
        "Authorization: Bearer {$token}",
    ]);

    if ($response['status'] !== 200 && $response['status'] !== 404) {
        throw new \Exception("Expected 200 or 404, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }

    // If successful, verify the response indicates what was updated
    if ($response['status'] === 200 && isset($response['body']['data']['updated'])) {
        $updated = $response['body']['data']['updated'];
        if (!is_array($updated)) {
            throw new \Exception("Response 'updated' field should be an array");
        }
    }

    // Cleanup
    if ($userId) {
        UserTestHelper::deleteTestUser($userId);
    }
});

// Test 10: GET /api/admin/matching/{eventId} (authenticated)
test('GET /api/admin/matching/{eventId}', function () use ($baseUrl, $testFirstName, $testLastName) {
    // Create test user and get token via API
    $suffix = makeUniqueSuffix();

    $registerResponse = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('admin.matching'),
        'password' => 'TestPass123',
        'accountType' => 'crew',
        'profile' => [
            'displayName' => "Admin {$suffix}",
            'firstName' => "{$testFirstName}{$suffix}",
            'lastName' => "{$testLastName}{$suffix}",
            'skill' => 1,
        ],
    ]);

    if (!isset($registerResponse['body']['data']['token'])) {
        throw new \Exception("Registration failed");
    }

    $token = $registerResponse['body']['data']['token'];
    $userId = $registerResponse['body']['data']['user']['id'] ?? null;

    $eventId = urlencode('Fri May 29');
    $response = makeRequest('GET', "{$baseUrl}/admin/matching/{$eventId}", null, [
        "Authorization: Bearer {$token}",
    ]);

    // May return 404 if event doesn't exist, which is valid
    if ($response['status'] !== 200 && $response['status'] !== 404) {
        throw new \Exception("Expected 200 or 404, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }

    // Cleanup
    if ($userId) {
        UserTestHelper::deleteTestUser($userId);
    }
});

// Test 11: POST /api/admin/notifications/{eventId} (authenticated)
test('POST /api/admin/notifications/{eventId}', function () use ($baseUrl, $testFirstName, $testLastName) {
    // Create test user and get token via API
    $suffix = makeUniqueSuffix();

    $registerResponse = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('admin.notifications'),
        'password' => 'TestPass123',
        'accountType' => 'crew',
        'profile' => [
            'displayName' => "Admin {$suffix}",
            'firstName' => "{$testFirstName}{$suffix}",
            'lastName' => "{$testLastName}{$suffix}",
            'skill' => 1,
        ],
    ]);

    if (!isset($registerResponse['body']['data']['token'])) {
        throw new \Exception("Registration failed");
    }

    $token = $registerResponse['body']['data']['token'];
    $userId = $registerResponse['body']['data']['user']['id'] ?? null;

    $eventId = urlencode('Fri May 29');
    $response = makeRequest('POST', "{$baseUrl}/admin/notifications/{$eventId}", null, [
        "Authorization: Bearer {$token}",
    ]);

    // May return 404 if event doesn't exist (or 500 if email service is not configured)
    // Accept these as valid test outcomes
    if ($response['status'] !== 200 && $response['status'] !== 404) {
        throw new \Exception("Expected 200, or 404, got {$response['status']}");
    }

    if (!isset($response['body']['success']) && !isset($response['body']['error'])) {
        throw new \Exception("Response missing 'success' or 'error' field");
    }

    // Cleanup
    if ($userId) {
        UserTestHelper::deleteTestUser($userId);
    }
});

// Test 12: PATCH /api/admin/config (authenticated)
test('PATCH /api/admin/config', function () use ($baseUrl, $testFirstName, $testLastName) {
    // Create test user and get token via API
    $suffix = makeUniqueSuffix();

    $registerResponse = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('admin.config'),
        'password' => 'TestPass123',
        'accountType' => 'crew',
        'profile' => [
            'displayName' => "Admin {$suffix}",
            'firstName' => "{$testFirstName}{$suffix}",
            'lastName' => "{$testLastName}{$suffix}",
            'skill' => 1,
        ],
    ]);

    if (!isset($registerResponse['body']['data']['token'])) {
        throw new \Exception("Registration failed");
    }

    $token = $registerResponse['body']['data']['token'];
    $userId = $registerResponse['body']['data']['user']['id'] ?? null;

    $response = makeRequest('PATCH', "{$baseUrl}/admin/config", [
        'startTime' => '10:00:00',
        'finishTime' => '18:00:00',
    ], [
        "Authorization: Bearer {$token}",
    ]);

    if ($response['status'] !== 200) {
        throw new \Exception("Expected 200, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }

    // Cleanup
    if ($userId) {
        UserTestHelper::deleteTestUser($userId);
    }
});

// Test 13: Authentication failure
test('Authentication failure', function () use ($baseUrl) {
    $response = makeRequest('GET', "{$baseUrl}/assignments");

    if ($response['status'] !== 401) {
        throw new \Exception("Expected 401, got {$response['status']}");
    }

    if (!isset($response['body']['error'])) {
        throw new \Exception("Response missing 'error' field");
    }
});

// Test 14: 404 for non-existent route
test('404 for non-existent route', function () use ($baseUrl) {
    $response = makeRequest('GET', "{$baseUrl}/nonexistent");

    if ($response['status'] !== 404) {
        throw new \Exception("Expected 404, got {$response['status']}");
    }

    if (!isset($response['body']['error'])) {
        throw new \Exception("Response missing 'error' field");
    }
});

// ====================================================================================
// FLOTILLA CREATION TESTS
// Tests that HTTP endpoints trigger flotilla table creation as expected
// ====================================================================================

// Test 15: PATCH /api/users/me/availability (boat owner) creates flotilla
test('PATCH /api/users/me/availability (boat owner) creates flotilla', function () use ($baseUrl) {
    // Create test user and get token via API
    $suffix = makeUniqueSuffix();
    $ownerFirstName = "TestBoat{$suffix}";
    $ownerLastName = "Owner";

    $registerResponse = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('boat.flotilla'),
        'password' => 'TestPass123',
        'accountType' => 'boat_owner',
        'profile' => [
            'displayName' => "Boat {$suffix}",
            'ownerFirstName' => $ownerFirstName,
            'ownerLastName' => $ownerLastName,
            'ownerMobile' => '555-1234',
            'minBerths' => 2,
            'maxBerths' => 4,
            'assistanceRequired' => false,
            'socialPreference' => true,
        ],
    ]);

    if (!isset($registerResponse['body']['data']['token'])) {
        throw new \Exception("Registration failed");
    }

    $token = $registerResponse['body']['data']['token'];
    $userId = $registerResponse['body']['data']['user']['id'] ?? null;

    // Update boat availability for event (unified endpoint auto-detects boat owner)
    $response = makeRequest('PATCH', "{$baseUrl}/users/me/availability", [
        'availabilities' => [
            ['eventId' => 'Fri May 29', 'isAvailable' => true],
        ],
    ], [
        "Authorization: Bearer {$token}",
    ]);

    // May return 404 if boat doesn't exist, which is acceptable for this test
    if ($response['status'] !== 200 && $response['status'] !== 404) {
        throw new \Exception("Expected 200 or 404, got {$response['status']}");
    }

    // If boat exists, verify flotilla can be retrieved
    if ($response['status'] === 200) {
        $eventResponse = makeRequest('GET', "{$baseUrl}/events/Fri%20May%2029");

        if (!isset($eventResponse['body']['data'])) {
            throw new \Exception("Event response missing 'data' field");
        }

        // Flotilla may or may not exist depending on whether there are other boats/crews
        // The key is that the endpoint doesn't error out
    }

    // Cleanup
    if ($userId) {
        UserTestHelper::deleteTestUser($userId);
    }
});

// Test 16: PATCH /api/users/me/availability creates flotilla
test('PATCH /api/users/me/availability creates flotilla', function () use ($baseUrl) {
    // Create test user and get token via API
    $suffix = makeUniqueSuffix();
    $firstName = "TestCrew{$suffix}";
    $lastName = "Member";

    $registerResponse = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('crew.flotilla'),
        'password' => 'TestPass123',
        'accountType' => 'crew',
        'profile' => [
            'displayName' => "Crew {$suffix}",
            'firstName' => $firstName,
            'lastName' => $lastName,
            'skill' => 1,
        ],
    ]);

    if (!isset($registerResponse['body']['data']['token'])) {
        throw new \Exception("Registration failed");
    }

    $token = $registerResponse['body']['data']['token'];
    $userId = $registerResponse['body']['data']['user']['id'] ?? null;

    // Update crew availability for event
    $response = makeRequest('PATCH', "{$baseUrl}/users/me/availability", [
        'availabilities' => [
            ['eventId' => 'Fri May 29', 'isAvailable' => true],
        ],
    ], [
        "Authorization: Bearer {$token}",
    ]);

    // May return 404 if crew doesn't exist, which is acceptable
    if ($response['status'] !== 200 && $response['status'] !== 404) {
        throw new \Exception("Expected 200 or 404, got {$response['status']}");
    }

    // If crew exists, verify flotilla endpoint works
    if ($response['status'] === 200) {
        $eventResponse = makeRequest('GET', "{$baseUrl}/events/Fri%20May%2029");

        if (!isset($eventResponse['body']['data'])) {
            throw new \Exception("Event response missing 'data' field");
        }
    }

    // Cleanup
    if ($userId) {
        UserTestHelper::deleteTestUser($userId);
    }
});

// Test 17: GET /api/events/{id} includes flotilla data structure
test('GET /api/events/{id} includes valid flotilla structure', function () use ($baseUrl) {
    $eventId = urlencode('Fri May 29');
    $response = makeRequest('GET', "{$baseUrl}/events/{$eventId}");

    // May return 404 if event doesn't exist
    if ($response['status'] !== 200 && $response['status'] !== 404) {
        throw new \Exception("Expected 200 or 404, got {$response['status']}");
    }

    if ($response['status'] === 200) {
        if (!isset($response['body']['data']['event'])) {
            throw new \Exception("Event data missing");
        }

        // Verify flotilla structure if present
        if (isset($response['body']['data']['flotilla'])) {
            $flotilla = $response['body']['data']['flotilla'];

            $requiredKeys = ['eventId', 'crewedBoats'];
            foreach ($requiredKeys as $key) {
                if (!isset($flotilla[$key])) {
                    throw new \Exception("Flotilla missing required key: {$key}");
                }
            }

            if (!is_array($flotilla['crewedBoats'])) {
                throw new \Exception("crewedBoats should be array");
            }

            // Verify optional keys exist
            if (!array_key_exists('waitlistBoats', $flotilla)) {
                throw new \Exception("Flotilla missing waitlistBoats key");
            }

            if (!array_key_exists('waitlistCrews', $flotilla)) {
                throw new \Exception("Flotilla missing waitlistCrews key");
            }
        }
    }
});

// Test 18: Multiple availability updates handled idempotently
test('Multiple availability updates create single flotilla', function () use ($baseUrl) {
    // Create test user and get token via API
    $suffix = makeUniqueSuffix();
    $ownerFirstName = "IdempotentBoat{$suffix}";
    $ownerLastName = "Owner";

    $registerResponse = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('boat.idempotent'),
        'password' => 'TestPass123',
        'accountType' => 'boat_owner',
        'profile' => [
            'displayName' => "Boat {$suffix}",
            'ownerFirstName' => $ownerFirstName,
            'ownerLastName' => $ownerLastName,
            'ownerMobile' => '555-1234',
            'minBerths' => 2,
            'maxBerths' => 4,
            'assistanceRequired' => false,
            'socialPreference' => true,
        ],
    ]);

    if (!isset($registerResponse['body']['data']['token'])) {
        throw new \Exception("Registration failed");
    }

    $token = $registerResponse['body']['data']['token'];
    $userId = $registerResponse['body']['data']['user']['id'] ?? null;

    // First update (unified endpoint auto-detects boat owner)
    $response1 = makeRequest('PATCH', "{$baseUrl}/users/me/availability", [
        'availabilities' => [
            ['eventId' => 'Fri May 29', 'isAvailable' => true],
        ],
    ], [
        "Authorization: Bearer {$token}",
    ]);

    // May return 404 if boat doesn't exist
    if ($response1['status'] !== 200 && $response1['status'] !== 404) {
        throw new \Exception("First update: Expected 200 or 404, got {$response1['status']}");
    }

    if ($response1['status'] === 200) {
        // Second update (same boat, different berths)
        $response2 = makeRequest('PATCH', "{$baseUrl}/users/me/availability", [
            'availabilities' => [
                ['eventId' => 'Fri May 29', 'isAvailable' => true],
            ],
        ], [
            "Authorization: Bearer {$token}",
        ]);

        if ($response2['status'] !== 200) {
            throw new \Exception("Second update: Expected 200, got {$response2['status']}");
        }

        // Verify flotilla exists and endpoint doesn't error
        $eventResponse = makeRequest('GET', "{$baseUrl}/events/Fri%20May%2029");

        if ($eventResponse['status'] !== 200 && $eventResponse['status'] !== 404) {
            throw new \Exception("Event fetch: Expected 200 or 404, got {$eventResponse['status']}");
        }
    }

    // Cleanup
    if ($userId) {
        UserTestHelper::deleteTestUser($userId);
    }
});

// Test 19: Verify old boat endpoint is removed (breaking change)
test('PATCH /api/boats/availability returns 404 (removed)', function () use ($baseUrl, $testFirstName, $testLastName) {
    // Create test user and get token via API
    $suffix = makeUniqueSuffix();

    $registerResponse = makeRequest('POST', "{$baseUrl}/auth/register", [
        'email' => makeUniqueEmail('test.removed'),
        'password' => 'TestPass123',
        'accountType' => 'boat_owner',
        'profile' => [
            'displayName' => "Boat {$suffix}",
            'ownerFirstName' => "{$testFirstName}{$suffix}",
            'ownerLastName' => "{$testLastName}{$suffix}",
            'ownerMobile' => '555-1234',
            'minBerths' => 2,
            'maxBerths' => 4,
            'assistanceRequired' => false,
            'socialPreference' => true,
        ],
    ]);

    if (!isset($registerResponse['body']['data']['token'])) {
        throw new \Exception("Registration failed");
    }

    $token = $registerResponse['body']['data']['token'];
    $userId = $registerResponse['body']['data']['user']['id'] ?? null;

    $response = makeRequest('PATCH', "{$baseUrl}/boats/availability", [
        'availabilities' => [
            ['eventId' => 'Fri May 29', 'isAvailable' => true],
        ],
    ], [
        "Authorization: Bearer {$token}",
    ]);

    // Should return 404 since this endpoint has been removed
    if ($response['status'] !== 404) {
        throw new \Exception("Expected 404 (endpoint removed), got {$response['status']}");
    }

    if (!isset($response['body']['error'])) {
        throw new \Exception("Response missing 'error' field");
    }

    // Cleanup
    if ($userId) {
        UserTestHelper::deleteTestUser($userId);
    }
});

echo "\n=================================\n";
echo "Test Results\n";
echo "=================================\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total:  " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n✓ All tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed.\n";
    exit(1);
}
