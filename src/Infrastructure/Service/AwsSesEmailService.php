<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Application\Port\Service\EmailServiceInterface;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

/**
 * AWS SES Email Service
 *
 * Implements email sending via AWS Simple Email Service (SES) using AWS SDK.
 * Supports LocalStack for local development/testing.
 */
class AwsSesEmailService implements EmailServiceInterface
{
    private SesClient $sesClient;
    private string $defaultFromEmail;
    private string $defaultFromName;

    public function __construct(
        ?string $region = null,
        ?string $accessKeyId = null,
        ?string $secretAccessKey = null,
        ?string $endpoint = null,
        ?string $defaultFromEmail = null,
        ?string $defaultFromName = null
    ) {
        // Use environment variables if parameters not provided
        $region = $region ?? getenv('SES_REGION') ?: 'ca-central-1';
        $accessKeyId = $accessKeyId ?? getenv('SES_SMTP_USERNAME') ?: getenv('AWS_ACCESS_KEY_ID') ?: 'test';
        $secretAccessKey = $secretAccessKey ?? getenv('SES_SMTP_PASSWORD') ?: getenv('AWS_SECRET_ACCESS_KEY') ?: 'test';
        $endpoint = $endpoint ?? getenv('SES_ENDPOINT') ?: null;
        $this->defaultFromEmail = $defaultFromEmail ?? getenv('EMAIL_FROM') ?: 'noreply@example.com';
        $this->defaultFromName = $defaultFromName ?? getenv('EMAIL_FROM_NAME') ?: 'JAWS System';

        // Configure SES client
        $config = [
            'region' => $region,
            'version' => 'latest',
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $secretAccessKey,
            ],
        ];

        // Add endpoint for LocalStack
        if ($endpoint !== null && $endpoint !== '') {
            $config['endpoint'] = $endpoint;
            // Disable SSL verification for LocalStack
            $config['http'] = [
                'verify' => false,
            ];
        }

        $this->sesClient = new SesClient($config);
    }

    public function send(
        string $to,
        string $subject,
        string $body,
        ?string $fromName = null,
        ?string $fromEmail = null
    ): bool {
        try {
            $from = $fromEmail ?? $this->defaultFromEmail;
            $name = $fromName ?? $this->defaultFromName;

            // Format sender with name
            $sender = $name ? "{$name} <{$from}>" : $from;

            $result = $this->sesClient->sendEmail([
                'Source' => $sender,
                'Destination' => [
                    'ToAddresses' => [$to],
                ],
                'Message' => [
                    'Subject' => [
                        'Data' => $subject,
                        'Charset' => 'UTF-8',
                    ],
                    'Body' => [
                        'Html' => [
                            'Data' => $body,
                            'Charset' => 'UTF-8',
                        ],
                    ],
                ],
            ]);

            // Check if email was accepted
            $messageId = $result->get('MessageId');
            if ($messageId) {
                error_log("Email sent successfully. MessageId: {$messageId}");
                return true;
            }

            return false;

        } catch (AwsException $e) {
            error_log("Email send failed: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            error_log("Email send failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendBulk(
        array $recipients,
        string $subject,
        string $body,
        ?string $fromName = null,
        ?string $fromEmail = null
    ): array {
        $results = [];

        foreach ($recipients as $recipient) {
            $results[$recipient] = $this->send($recipient, $subject, $body, $fromName, $fromEmail);
        }

        return $results;
    }

    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
