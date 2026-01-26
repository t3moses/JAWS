<?php

declare(strict_types=1);

/**
 * Simple API Test Script
 *
 * Basic tests to verify API endpoints are working.
 * Run with: php tests/api_test.php
 */

// Configuration
$baseUrl = 'http://localhost/api';
$testFirstName = 'John';
$testLastName = 'Doe';

// Helper function to make HTTP requests
function makeRequest(string $method, string $url, ?array $body = null, ?array $headers = []): array
{
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

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
    global $passed, $failed;

    echo "\nTest: {$name}\n";

    try {
        $fn();
        echo "✓ PASSED\n";
        $passed++;
    } catch (\Exception $e) {
        echo "✗ FAILED: {$e->getMessage()}\n";
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
    $response = makeRequest('GET', "{$baseUrl}/events/Fri May 29");

    // May return 404 if event doesn't exist, which is valid
    if ($response['status'] !== 200 && $response['status'] !== 404) {
        throw new \Exception("Expected 200 or 404, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }
});

// Test 3: POST /api/crews/register (authenticated)
test('POST /api/crews/register', function () use ($baseUrl, $testFirstName, $testLastName) {
    $response = makeRequest('POST', "{$baseUrl}/crews/register", [
        'display_name' => 'John Doe',
        'first_name' => $testFirstName,
        'last_name' => $testLastName,
        'email' => 'john.doe@example.com',
        'skill' => 1,
    ], [
        "X-User-FirstName: {$testFirstName}",
        "X-User-LastName: {$testLastName}",
    ]);

    if ($response['status'] !== 201 && $response['status'] !== 200) {
        throw new \Exception("Expected 201 or 200, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }
});

// Test 4: PATCH /api/users/me/availability (authenticated)
test('PATCH /api/users/me/availability', function () use ($baseUrl, $testFirstName, $testLastName) {
    $response = makeRequest('PATCH', "{$baseUrl}/users/me/availability", [
        'availabilities' => [
            'Fri May 29' => 1,
        ],
    ], [
        "X-User-FirstName: {$testFirstName}",
        "X-User-LastName: {$testLastName}",
    ]);

    if ($response['status'] !== 200 && $response['status'] !== 404) {
        throw new \Exception("Expected 200 or 404, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }
});

// Test 5: GET /api/assignments (authenticated)
test('GET /api/assignments', function () use ($baseUrl, $testFirstName, $testLastName) {
    $response = makeRequest('GET', "{$baseUrl}/assignments", null, [
        "X-User-FirstName: {$testFirstName}",
        "X-User-LastName: {$testLastName}",
    ]);

    if ($response['status'] !== 200 && $response['status'] !== 404) {
        throw new \Exception("Expected 200 or 404, got {$response['status']}");
    }

    if (!isset($response['body']['success'])) {
        throw new \Exception("Response missing 'success' field");
    }
});

// Test 6: Authentication failure
test('Authentication failure', function () use ($baseUrl) {
    $response = makeRequest('GET', "{$baseUrl}/assignments");

    if ($response['status'] !== 401) {
        throw new \Exception("Expected 401, got {$response['status']}");
    }

    if (!isset($response['body']['error'])) {
        throw new \Exception("Response missing 'error' field");
    }
});

// Test 7: 404 for non-existent route
test('404 for non-existent route', function () use ($baseUrl) {
    $response = makeRequest('GET', "{$baseUrl}/nonexistent");

    if ($response['status'] !== 404) {
        throw new \Exception("Expected 404, got {$response['status']}");
    }

    if (!isset($response['body']['error'])) {
        throw new \Exception("Response missing 'error' field");
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
