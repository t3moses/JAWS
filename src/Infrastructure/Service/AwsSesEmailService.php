<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Application\Port\Service\EmailServiceInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * AWS SES Email Service
 *
 * Implements email sending via AWS Simple Email Service (SES) using PHPMailer.
 */
class AwsSesEmailService implements EmailServiceInterface
{
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUsername;
    private string $smtpPassword;
    private string $defaultFromEmail;
    private string $defaultFromName;

    public function __construct(
        ?string $smtpHost = null,
        ?int $smtpPort = null,
        ?string $smtpUsername = null,
        ?string $smtpPassword = null,
        ?string $defaultFromEmail = null,
        ?string $defaultFromName = null
    ) {
        // Use environment variables if parameters not provided
        $this->smtpHost = $smtpHost ?? getenv('SES_SMTP_HOST') ?: 'email-smtp.ca-central-1.amazonaws.com';
        $this->smtpPort = $smtpPort ?? (int)(getenv('SES_SMTP_PORT') ?: 587);
        $this->smtpUsername = $smtpUsername ?? getenv('SES_SMTP_USERNAME') ?: '';
        $this->smtpPassword = $smtpPassword ?? getenv('SES_SMTP_PASSWORD') ?: '';
        $this->defaultFromEmail = $defaultFromEmail ?? getenv('SES_FROM_EMAIL') ?: 'noreply@example.com';
        $this->defaultFromName = $defaultFromName ?? getenv('SES_FROM_NAME') ?: 'JAWS System';
    }

    public function send(
        string $to,
        string $subject,
        string $body,
        ?string $fromName = null,
        ?string $fromEmail = null
    ): bool {
        $mail = $this->createMailer();

        try {
            // Sender
            $mail->setFrom(
                $fromEmail ?? $this->defaultFromEmail,
                $fromName ?? $this->defaultFromName
            );

            // Recipient
            $mail->addAddress($to);

            // Content
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(true);

            return $mail->send();

        } catch (PHPMailerException $e) {
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

    /**
     * Create configured PHPMailer instance
     */
    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = $this->smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $this->smtpUsername;
        $mail->Password = $this->smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $this->smtpPort;

        // Encoding
        $mail->CharSet = 'UTF-8';

        return $mail;
    }
}
