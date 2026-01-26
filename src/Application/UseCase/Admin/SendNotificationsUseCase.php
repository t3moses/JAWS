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
                $calendarAttachment = $this->calendarService->generateEventCalendar(
                    $eventId->toString(),
                    $eventData['event_date'],
                    $eventData['start_time'],
                    $eventData['finish_time'],
                    $boat->getDisplayName(),
                    $crews
                );
            }

            // Send email to boat owner
            $this->emailService->sendAssignmentNotification(
                $boat->getOwnerEmail(),
                $boat->getOwnerFirstName(),
                $eventId->toString(),
                $boat->getDisplayName(),
                $crews,
                $calendarAttachment
            );
            $emailsSent++;

            // Send email to each crew member
            foreach ($crews as $crew) {
                $this->emailService->sendAssignmentNotification(
                    $crew->getEmail(),
                    $crew->getFirstName(),
                    $eventId->toString(),
                    $boat->getDisplayName(),
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
}
