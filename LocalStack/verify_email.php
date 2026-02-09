<?php

/**
 * LocalStack Email Verification Script
 *
 * Verifies email addresses in LocalStack SES so they can be used to send emails.
 * This must be run once before sending emails through LocalStack.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Aws\Ses\SesClient;

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
    foreach ($_ENV as $key => $value) {
        if (!getenv($key)) {
            putenv("{$key}={$value}");
        }
    }
}

// Configuration
$region = getenv('SES_REGION') ?: 'ca-central-1';
$accessKey = getenv('SES_SMTP_USERNAME') ?: getenv('AWS_ACCESS_KEY_ID') ?: 'test';
$secretKey = getenv('SES_SMTP_PASSWORD') ?: getenv('AWS_SECRET_ACCESS_KEY') ?: 'test';
$endpoint = getenv('SES_ENDPOINT') ?: 'http://localhost:4566';
$emailAddress = getenv('EMAIL_FROM') ?: 'noreply@nsc-sdc.ca';

echo "LocalStack Email Verification\n";
echo str_repeat("=", 50) . "\n\n";
echo "Configuration:\n";
echo "  Region: {$region}\n";
echo "  Endpoint: {$endpoint}\n";
echo "  Email: {$emailAddress}\n\n";

// Create SES client
$sesClient = new SesClient([
    'region' => $region,
    'version' => 'latest',
    'credentials' => [
        'key' => $accessKey,
        'secret' => $secretKey,
    ],
    'endpoint' => $endpoint,
    'http' => [
        'verify' => false,
    ],
]);

// Verify email address
echo "Verifying email address...\n";
try {
    $result = $sesClient->verifyEmailIdentity([
        'EmailAddress' => $emailAddress,
    ]);
    echo "✓ Email address verified successfully!\n\n";
} catch (Exception $e) {
    echo "✗ Failed to verify email: " . $e->getMessage() . "\n";
    exit(1);
}

// List all verified email addresses
echo "Listing all verified email addresses:\n";
try {
    $result = $sesClient->listVerifiedEmailAddresses();
    $addresses = $result->get('VerifiedEmailAddresses');

    if (empty($addresses)) {
        echo "  (No verified email addresses found)\n";
    } else {
        foreach ($addresses as $address) {
            echo "  ✓ {$address}\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Failed to list verified emails: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Email verification complete!\n";
echo "You can now send emails through LocalStack.\n";
