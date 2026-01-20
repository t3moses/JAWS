<?php

namespace nsc\sdc\calendar;

require __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Season/src/Season.php';

use nsc\sdc\season as season;

use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\Enum\EventStatus;
use Eluceo\iCal\Domain\ValueObject\UniqueIdentifier;
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

function get_event_ical( string $_event_id ) {

    $_date = date_create_from_format( 'Y D M j H i s', season\Season::$_year . ' ' . $_event_id . ' ' . season\Season::$_start_time );
    return $_date->format('Y-m-d');
}

function program() {

    // Create the calendar for all future events

    season\Season::load_season_data();
    $_event_ids = season\Season::get_future_events();

    $events = [];

    foreach ( $_event_ids as $_event_id) {

        // Create event with start time 12:45 and end time 17:00

        $_ical_date = get_event_ical( $_event_id );
        $event = new Event(new UniqueIdentifier( 'ca/nsc/sdc/' . season\Season::$_year . '/program' ));

        $event
            ->setSummary('Social Day Cruising')
            ->setDescription('Event date')
            ->setOccurrence(
                new TimeSpan(
                        new DateTime(new \DateTimeImmutable($_ical_date . ' ' . str_replace(" ", ":", season\Season::$_start_time)), false),
                        new DateTime(new \DateTimeImmutable($_ical_date . ' ' . str_replace(" ", ":", season\Season::$_finish_time)), false)
                )
            )
            ->setStatus(EventStatus::confirmed());

        // Add an alarm 1 day before the event
        $alarmInterval = new \DateInterval('P1D');
        $alarmInterval->invert = 1;
        $event->addAlarm(
            new Alarm(new AudioAction(),
                new RelativeTrigger($alarmInterval)
            )
        );

        $organizer = new Organizer(
            new EmailAddress('nsc-sdc@nsc.ca'),
            'Admin'
        );
        $event->setOrganizer($organizer);

        $event->setStatus(EventStatus::confirmed());

        // Set location (optional - can provide route planning in some calendar apps)
        $location = new Location('Nepean Sailing Club');
        $location = $location->withGeographicPosition(new GeographicPosition(45.352138, -75.827229));
        $event->setLocation($location);

        // Add a link to the event description (optional - some calendar apps support this)
        $attachment = new Attachment(
            new Uri('https://nsc.ca/an/cruising/social-cruising/social-day-cruising/'),
            'text/html'
        );
        $event->addAttachment($attachment);

        $events[] = $event;
    }

    // Create a calendar and add all events
    $_update_calendar = new Calendar($events);

    // Generate the .ics file content
    $updateFactory = new CalendarFactory();
    $updateComponent = $updateFactory->createCalendar($_update_calendar);

    $_update = str_replace( 'BEGIN:VCALENDAR', 'BEGIN:VCALENDAR' . "\n" . 'METHOD:PUBLISH', $updateComponent->__toString() );

    $_utime = (string)time();
    $_update = str_replace( 'BEGIN:VEVENT', 'BEGIN:VEVENT' . "\n" . 'SEQUENCE:' . $_utime, $_update );

    $_filename = __DIR__ . '/../data/update.ics';
    file_put_contents( $_filename, $_update );    

}



function boat( $_boat ) {

    // Create the individual boat calendar for all future events

    season\Season::load_season_data();
    $_event_ids = season\Season::get_future_events();

    $_register_events = [];
    $_cancel_events = [];

    foreach ( $_event_ids as $_event_id) {

        // Create event with start time 12:45 and end time 17:00
        // Branch according to whether the boat is available or not.

        $_ical_date = get_event_ical( $_event_id );

        $_available = (int)$_boat->berths[ $_event_id ] > 0;

        if ( $_available ) {

            // Add the event to the register file.

            $_register_event = new Event(new UniqueIdentifier('ca/nsc/sdc/' . season\Season::$_year . '/' . str_replace(" ", "-", $_event_id) . '/' . $_boat->key ));

            $_register_event
                ->setSummary('Social Day-cruising')
                ->setDescription($_boat->get_display_name() . ' is available')
                ->setOccurrence(
                    new TimeSpan(
                        new DateTime(new \DateTimeImmutable($_ical_date . ' ' . str_replace(" ", ":", season\Season::$_start_time)), false),
                        new DateTime(new \DateTimeImmutable($_ical_date . ' ' . str_replace(" ", ":", season\Season::$_finish_time)), false)
                    )
                );

            // Add an alarm 1 day before the event
            $alarmInterval = new \DateInterval('P1D');
            $alarmInterval->invert = 1;
            $_register_event->addAlarm(
                new Alarm(new AudioAction(),
                    new RelativeTrigger($alarmInterval)
                )
            );
    
            $organizer = new Organizer(
                new EmailAddress('nsc-sdc@nsc.ca'),
                'nsc-sdc'
            );
            $_register_event->setOrganizer($organizer);

            $_register_event->setStatus(EventStatus::confirmed());

            // Set location (optional - can provide route planning in some calendar apps)
            $location = new Location('Nepean Sailing Club');
            $location = $location->withGeographicPosition(new GeographicPosition(45.352138, -75.827229));
            $_register_event->setLocation($location);

            // Add a link to the event description (optional - some calendar apps support this)
            $attachment = new Attachment(
                new Uri('https://nsc.ca/an/cruising/social-cruising/social-day-cruising/'),
                'text/html'
            );
            $_register_event->addAttachment($attachment);

            $_register_events[] = $_register_event;
        }
        else {

            // Add the event to the cancel file.

            $_cancel_event = new Event(new UniqueIdentifier('ca/nsc/sdc/' . season\Season::$_year . '/' . str_replace(" ", "-", $_event_id) . '/' . $_boat->key ));

            $_cancel_event
                ->setSummary('Social Day-cruising')
                ->setDescription($_boat->get_display_name() . ' is not available')
                ->setOccurrence(
                    new TimeSpan(
                        new DateTime(new \DateTimeImmutable($_ical_date . ' ' . str_replace(" ", ":", season\Season::$_start_time)), false),
                        new DateTime(new \DateTimeImmutable($_ical_date . ' ' . str_replace(" ", ":", season\Season::$_finish_time)), false)
                    )
                );

            $organizer = new Organizer(
                new EmailAddress('nsc-sdc@nsc.ca'),
                'nsc-sdc'
            );
            $_cancel_event->setOrganizer($organizer);

            $_cancel_event->setStatus(EventStatus::cancelled());

            $_cancel_events[] = $_cancel_event;
        }
    }

    // Create calendars for the publish and cancel events
    $_register_calendar = new Calendar($_register_events);
    $_cancel_calendar = new Calendar($_cancel_events);

    // Generate the .ics file containing register events
    $registerFactory = new CalendarFactory();
    $registerComponent = $registerFactory->createCalendar($_register_calendar);

    // Generate the .ics file containing cancel events
    $cancelFactory = new CalendarFactory();
    $cancelComponent = $cancelFactory->createCalendar($_cancel_calendar);

    $_register_update = str_replace( 'BEGIN:VCALENDAR', 'BEGIN:VCALENDAR' . "\n" . 'METHOD:PUBLISH', $registerComponent->__toString() );
    $_cancel_update = str_replace( 'BEGIN:VCALENDAR', 'BEGIN:VCALENDAR' . "\n" . 'METHOD:CANCEL', $cancelComponent->__toString() );

    $_utime = (string)time();
    $_register_update = str_replace( 'BEGIN:VEVENT', 'BEGIN:VEVENT' . "\n" . 'SEQUENCE:' . $_utime, $_register_update );
    $_cancel_update = str_replace( 'BEGIN:VEVENT', 'BEGIN:VEVENT' . "\n" . 'SEQUENCE:' . $_utime, $_cancel_update );

    $_update = $_register_update . $_cancel_update;

    $_register_filename = __DIR__ . '/../data/register.ics';
    file_put_contents( $_register_filename, $_register_update );

    $_cancel_filename = __DIR__ . '/../data/cancel.ics';
    file_put_contents( $_cancel_filename, $_cancel_update );

    $_update_filename = __DIR__ . '/../data/update.ics';
    file_put_contents( $_update_filename, $_update );

}


function crew( $_crew ) {

    // Create the individual boat calendar for all future events

    season\Season::load_season_data();
    $_event_ids = season\Season::get_future_events();

    $_register_events = [];
    $_cancel_events = [];

    foreach ( $_event_ids as $_event_id) {

        // Create event with start time 12:45 and end time 17:00
        // Branch according to whether the crew is available or not.

        $_ical_date = get_event_ical( $_event_id );

        $_available = (int)$_crew->available[ $_event_id ] > 0;
        
        if ( $_available ) {

            // Add the event to the register file.

            $_register_event = new Event(new UniqueIdentifier('ca/nsc/sdc/' . season\Season::$_year . '/' . str_replace(" ", "-", $_event_id) . '/' . $_crew->key ));

            $_register_event
                ->setSummary('Social Day-cruising')
                ->setDescription($_crew->get_display_name() . ' is available to crew')
                ->setOccurrence(
                    new TimeSpan(
                        new DateTime(new \DateTimeImmutable($_ical_date . ' ' . str_replace(" ", ":", season\Season::$_start_time)), false),
                        new DateTime(new \DateTimeImmutable($_ical_date . ' ' . str_replace(" ", ":", season\Season::$_finish_time)), false)
                    )
                );

            // Add an alarm 1 day before the event
            $alarmInterval = new \DateInterval('P1D');
            $alarmInterval->invert = 1;
            $_register_event->addAlarm(
                new Alarm(new AudioAction(),
                    new RelativeTrigger($alarmInterval)
                )
            );

            $organizer = new Organizer(
                new EmailAddress('nsc-sdc@nsc.ca'),
                'nsc-sdc'
            );
            $_register_event->setOrganizer($organizer);

            $_register_event->setStatus(EventStatus::confirmed());

            // Set location (optional - can provide route planning in some calendar apps)
            $location = new Location('Nepean Sailing Club');
            $location = $location->withGeographicPosition(new GeographicPosition(45.352138, -75.827229));
            $_register_event->setLocation($location);

            // Add a link to the event description (optional - some calendar apps support this)
            $attachment = new Attachment(
                new Uri('https://nsc.ca/an/cruising/social-cruising/social-day-cruising/'),
                'text/html'
            );
            $_register_event->addAttachment($attachment);

            $_register_events[] = $_register_event;
        }
        else {

            // Add the event to the cancel file.

            $_cancel_event = new Event(new UniqueIdentifier('ca/nsc/sdc/' . season\Season::$_year . '/' . str_replace(" ", "-", $_event_id) . '/' . $_crew->key ));

            $_cancel_event
                ->setSummary('Social Day-cruising')
                ->setDescription($_crew->get_display_name() . ' is not available to crew')
                ->setOccurrence(
                    new TimeSpan(
                        new DateTime(new \DateTimeImmutable($_ical_date . ' ' . str_replace(" ", ":", season\Season::$_start_time)), false),
                        new DateTime(new \DateTimeImmutable($_ical_date . ' ' . str_replace(" ", ":", season\Season::$_finish_time)), false)
                    )
                );

            $organizer = new Organizer(
                new EmailAddress('nsc-sdc@nsc.ca'),
                'nsc-sdc'
            );
            $_cancel_event->setOrganizer($organizer);

            $_cancel_event->setStatus(EventStatus::cancelled());

            $_cancel_events[] = $_cancel_event;
        }
    }

    // Create calendars for the publish and cancel events
    $_register_calendar = new Calendar($_register_events);
    $_cancel_calendar = new Calendar($_cancel_events);

    // Generate the .ics file containing register events
    $registerFactory = new CalendarFactory();
    $registerComponent = $registerFactory->createCalendar($_register_calendar);

    // Generate the .ics file containing cancel events
    $cancelFactory = new CalendarFactory();
    $cancelComponent = $cancelFactory->createCalendar($_cancel_calendar);

    $_register_update = str_replace( 'BEGIN:VCALENDAR', 'BEGIN:VCALENDAR' . "\n" . 'METHOD:PUBLISH', $registerComponent->__toString() );
    $_cancel_update = str_replace( 'BEGIN:VCALENDAR', 'BEGIN:VCALENDAR' . "\n" . 'METHOD:CANCEL', $cancelComponent->__toString() );

    $_utime = (string)time();
    $_register_update = str_replace( 'BEGIN:VEVENT', 'BEGIN:VEVENT' . "\n" . 'SEQUENCE:' . $_utime, $_register_update );
    $_cancel_update = str_replace( 'BEGIN:VEVENT', 'BEGIN:VEVENT' . "\n" . 'SEQUENCE:' . $_utime, $_cancel_update );

    $_update = $_register_update . $_cancel_update;

    $_register_filename = __DIR__ . '/../data/register.ics';
    file_put_contents( $_register_filename, $_register_update );

    $_cancel_filename = __DIR__ . '/../data/cancel.ics';
    file_put_contents( $_cancel_filename, $_cancel_update );

    $_update_filename = __DIR__ . '/../data/update.ics';
    file_put_contents( $_update_filename, $_update );

}

?>