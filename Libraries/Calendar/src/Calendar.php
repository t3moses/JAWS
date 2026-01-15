
<?php

require __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Season/src/Season.php';

use nsc\sdc\season as season;

use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\DateTime;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Domain\ValueObject\Alarm;
use Eluceo\iCal\Domain\ValueObject\Alarm\AudioAction;
use Eluceo\iCal\Domain\ValueObject\Alarm\RelativeTrigger;
use Eluceo\iCal\Domain\ValueObject\Alarm\DisplayAlarm;
use Eluceo\iCal\Domain\ValueObject\Attachment;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\GeographicPosition;
use Eluceo\iCal\Domain\ValueObject\Location;
use Eluceo\iCal\Domain\ValueObject\Organizer;
use Eluceo\iCal\Domain\ValueObject\Uri;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;

function program_calendar() {

    season\Season::load_season_data();
    $eventDates = season\Season::get_future_ical();

    $events = [];

    foreach ($eventDates as $date) {
        // Create event with start time 13:00 and end time 16:00
        $event = new Event();
        $event
            ->setSummary('Social Day Cruising')
            ->setDescription('Social Day Cruising with Nepean Sailing Club.')
            ->setOccurrence(
                new TimeSpan(
                    new DateTime(new \DateTimeImmutable($date . ' 13:00:00'), false),
                    new DateTime(new \DateTimeImmutable($date . ' 16:00:00'), false)
                )
        );

        // Add an alarm 1 day before the event
        $alarmInterval = new \DateInterval('P1D');
        $alarmInterval->invert = 1;
        $event->addAlarm(
            new Alarm(new AudioAction(),
                new RelativeTrigger($alarmInterval)
            )
        );

        // Set location (optional - can provide route planning in some calendar apps)
        $location = new Location('Nepean Sailing Club');
        $location = $location->withGeographicPosition(new GeographicPosition(45.352138, -75.827229));
        $event->setLocation($location);
/*
        // Set the organizer (optional - can provide contact information)
        $organizer = new Organizer(
            new EmailAddress('test@example.org'),
            'John Doe'
        );
        $event->setOrganizer($organizer);
*/
        // Add a link to the event description (optional - some calendar apps support this)
        $attachment = new Attachment(
            new Uri('https://nsc.ca/an/cruising/social-cruising/social-day-cruising/'),
            'text/html'
        );
        $event->addAttachment($attachment);

        $events[] = $event;
    }

    // Create a calendar and add all events
    $calendar = new Calendar($events);

    // Generate the .ics file content
    $componentFactory = new CalendarFactory();
    $calendarComponent = $componentFactory->createCalendar($calendar);

    // Save to file
    $_filename = __DIR__ . '/../data/program.ics';
    file_put_contents( $_filename, $calendarComponent );

}

?>