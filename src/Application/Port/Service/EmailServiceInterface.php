<?php

declare(strict_types=1);

namespace App\Application\Port\Service;

/**
 * Email Service Interface
 *
 * Defines the contract for sending emails.
 * Implementations handle SMTP, AWS SES, or other email providers.
 */
interface EmailServiceInterface
{
    /**
     * Send an email
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML or plain text)
     * @param string|null $fromName Optional sender name
     * @param string|null $fromEmail Optional sender email
     * @return bool True if sent successfully
     */
    public function send(
        string $to,
        string $subject,
        string $body,
        ?string $fromName = null,
        ?string $fromEmail = null
    ): bool;

    /**
     * Send email to multiple recipients
     *
     * @param array<string> $recipients Array of email addresses
     * @param string $subject Email subject
     * @param string $body Email body
     * @param string|null $fromName Optional sender name
     * @param string|null $fromEmail Optional sender email
     * @return array<string, bool> Map of email => success status
     */
    public function sendBulk(
        array $recipients,
        string $subject,
        string $body,
        ?string $fromName = null,
        ?string $fromEmail = null
    ): array;

    /**
     * Validate an email address
     *
     * @param string $email
     * @return bool
     */
    public function validateEmail(string $email): bool;
}
