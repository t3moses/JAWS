<?php

declare(strict_types=1);

namespace App\Application\UseCase\Admin;

use App\Application\Exception\EventNotFoundException;
use App\Application\Port\Repository\EventRepositoryInterface;
use App\Application\Port\Repository\SeasonRepositoryInterface;
use App\Application\Port\Service\EmailServiceInterface;
use App\Application\Port\Service\CalendarServiceInterface;
use App\Domain\ValueObject\EventId;

/**
 * Send Notifications Use Case
 *
 * Sends email notifications and calendar invites to participants for an event.
 * This is an admin function typically triggered after flotilla assignments are finalized.
 */
class SendNotificationsUseCase
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private SeasonRepositoryInterface $seasonRepository,
        private EmailServiceInterface $emailService,
        private CalendarServiceInterface $calendarService,
    ) {
    }

    /**
     * Execute the use case
     *
     * @param EventId $eventId
     * @param bool $includeCalendar Whether to include calendar attachments
     * @return array{success: bool, emails_sent: int, message: string}
     * @throws EventNotFoundException
     */
    public function execute(EventId $eventId, bool $includeCalendar = true): array
    {
        // Verify event exists
        $eventData = $this->eventRepository->findById($eventId);
        if ($eventData === null) {
            throw new EventNotFoundException($eventId);
        }

        // Get flotilla data
        $flotilla = $this->seasonRepository->getFlotilla($eventId);
        if ($flotilla === null) {
            return [
                'success' => false,
                'emails_sent' => 0,
                'message' => 'No flotilla data available for this event',
            ];
        }

        $emailsSent = 0;

        // Send notifications to boat owners with crew assignments
        foreach ($flotilla['crewed_boats'] as $crewedBoat) {
            $boat = $crewedBoat['boat'];
            $crews = $crewedBoat['crews'];

            // Generate calendar invite if requested
            $calendarAttachment = null;
            if ($includeCalendar) {
                try {
                    // Build crew description for calendar
                    $crewNames = array_map(
                        fn($crew) => $crew['first_name'] . ' ' . $crew['last_name'],
                        $crews
                    );
                    $description = 'Crew: ' . implode(', ', $crewNames);

                    // Convert event_date string to DateTime if needed
                    if ($eventData['event_date'] instanceof \DateTimeInterface) {
                        $eventDate = $eventData['event_date'];
                    } else {
                        // Try parsing as date string (YYYY-MM-DD format)
                        $eventDate = \DateTimeImmutable::createFromFormat('Y-m-d', $eventData['event_date']);
                        if ($eventDate === false) {
                            // Fall back to standard parsing
                            $eventDate = new \DateTimeImmutable($eventData['event_date']);
                        }
                    }

                    $calendarAttachment = $this->calendarService->generateEventCalendar(
                        $eventId,
                        $eventDate,
                        $eventData['start_time'],
                        $eventData['finish_time'],
                        $boat['display_name'],
                        $description
                    );
                } catch (\Exception $e) {
                    // Log error but continue without calendar attachment
                    error_log("Failed to generate calendar: " . $e->getMessage());
                    $calendarAttachment = null;
                }
            }

            // Send email to boat owner
            $this->emailService->sendAssignmentNotification(
                $boat['owner_email'],
                $boat['owner_first_name'],
                $eventId->toString(),
                $boat['display_name'],
                $crews,
                $calendarAttachment
            );
            $emailsSent++;

            // Send email to each crew member
            foreach ($crews as $crew) {
                $this->emailService->sendAssignmentNotification(
                    $crew['email'],
                    $crew['first_name'],
                    $eventId->toString(),
                    $boat['display_name'],
                    $crews,
                    $calendarAttachment
                );
                $emailsSent++;
            }
        }

        return [
            'success' => true,
            'emails_sent' => $emailsSent,
            'message' => "Sent {$emailsSent} notification emails for event {$eventId->toString()}",
        ];
    }

    /**
     * Send assignment notification email
     *
     * @param string $recipientEmail Recipient's email address
     * @param string $recipientFirstName Recipient's first name
     * @param string $eventId Event identifier (e.g., "Fri May 29")
     * @param string $boatName Name of the boat
     * @param array $crews Array of crew member objects assigned to the boat
     * @param string|null $calendarAttachment Optional iCalendar attachment content
     * @return bool True if sent successfully
     */
    public function sendAssignmentNotification(
        string $recipientEmail,
        string $recipientFirstName,
        string $eventId,
        string $boatName,
        array $crews,
        ?string $calendarAttachment = null
    ): bool {
        $mail = $this->createMailer();

        try {
            // Sender
            $mail->setFrom($this->defaultFromEmail, $this->defaultFromName);

            // Recipient
            $mail->addAddress($recipientEmail);

            // Subject
            $mail->Subject = "Boat Assignment for {$eventId}";

            // Build email body
            $body = $this->buildAssignmentEmailBody($recipientFirstName, $eventId, $boatName, $crews);
            $mail->Body = $body;
            $mail->isHTML(true);

            // Add calendar attachment if provided
            if ($calendarAttachment !== null) {
                $mail->addStringAttachment(
                    $calendarAttachment,
                    "event_{$eventId}.ics",
                    PHPMailer::ENCODING_BASE64,
                    'text/calendar'
                );
            }

            return $mail->send();

        } catch (PHPMailerException $e) {
            error_log("Assignment notification email failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build HTML email body for assignment notification
     *
     * @param string $recipientFirstName
     * @param string $eventId
     * @param string $boatName
     * @param array $crews Array of crew data (arrays, not objects)
     */
    private function buildAssignmentEmailBody(
        string $recipientFirstName,
        string $eventId,
        string $boatName,
        array $crews
    ): string {
        $crewList = '';
        foreach ($crews as $crew) {
            $crewList .= sprintf(
                '<li>%s %s (%s) - Skill: %s</li>',
                htmlspecialchars($crew['first_name']),
                htmlspecialchars($crew['last_name']),
                htmlspecialchars($crew['email']),
                $this->getSkillLevelLabel($crew['skill'])
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #0066cc; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; }
        .boat-name { font-size: 1.2em; font-weight: bold; color: #0066cc; }
        .crew-list { background-color: white; padding: 15px; border-radius: 5px; margin-top: 15px; }
        ul { padding-left: 20px; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 0.9em; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Social Day Cruising - Assignment Notification</h2>
        </div>
        <div class="content">
            <p>Hi {$recipientFirstName},</p>

            <p>You have been assigned for the upcoming sailing event:</p>

            <p><strong>Event:</strong> {$eventId}</p>
            <p><strong>Boat:</strong> <span class="boat-name">{$boatName}</span></p>

            <div class="crew-list">
                <h3>Crew Members:</h3>
                <ul>
                    {$crewList}
                </ul>
            </div>

            <p>Please confirm your participation and coordinate with your crew members.</p>

            <div class="footer">
                <p>This is an automated notification from the JAWS (Just Another Web System) sailing management system.</p>
                <p>If you have any questions, please contact the sailing coordinator.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get human-readable skill level label
     */
    private function getSkillLevelLabel(int $skillLevel): string
    {
        return match($skillLevel) {
            0 => 'Novice',
            1 => 'Intermediate',
            2 => 'Advanced',
            default => 'Unknown'
        };
    }
}
