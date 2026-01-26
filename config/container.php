<?php

declare(strict_types=1);

/**
 * Dependency Injection Container
 *
 * This file configures all dependencies for the application following
 * the Dependency Inversion Principle. Controllers receive use cases,
 * use cases receive repositories/services (via interfaces), and
 * repositories/services receive concrete implementations.
 */

use App\Application\Port\Repository\BoatRepositoryInterface;
use App\Application\Port\Repository\CrewRepositoryInterface;
use App\Application\Port\Repository\EventRepositoryInterface;
use App\Application\Port\Repository\SeasonRepositoryInterface;
use App\Application\Port\Service\EmailServiceInterface;
use App\Application\Port\Service\CalendarServiceInterface;
use App\Application\Port\Service\TimeServiceInterface;
use App\Infrastructure\Persistence\SQLite\BoatRepository;
use App\Infrastructure\Persistence\SQLite\CrewRepository;
use App\Infrastructure\Persistence\SQLite\EventRepository;
use App\Infrastructure\Persistence\SQLite\SeasonRepository;
use App\Infrastructure\Service\AwsSesEmailService;
use App\Infrastructure\Service\ICalendarService;
use App\Infrastructure\Service\SystemTimeService;
use App\Domain\Service\SelectionService;
use App\Domain\Service\AssignmentService;
use App\Domain\Service\RankingService;
use App\Domain\Service\FlexService;

// Simple service container
class Container
{
    private array $services = [];
    private array $factories = [];

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new Exception("Service '{$id}' not found in container");
        }

        $this->services[$id] = $this->factories[$id]($this);
        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]) || isset($this->services[$id]);
    }
}

// Create container
$container = new Container();

// =======================
// Infrastructure Layer
// =======================

// Repositories (Persistence)
$container->set(BoatRepositoryInterface::class, function () {
    return new BoatRepository();
});

$container->set(CrewRepositoryInterface::class, function () {
    return new CrewRepository();
});

$container->set(EventRepositoryInterface::class, function () {
    return new EventRepository();
});

$container->set(SeasonRepositoryInterface::class, function () {
    return new SeasonRepository();
});

// Services (External Adapters)
$container->set(EmailServiceInterface::class, function () {
    return new AwsSesEmailService();
});

$container->set(CalendarServiceInterface::class, function () {
    return new ICalendarService();
});

$container->set(TimeServiceInterface::class, function () {
    return new SystemTimeService();
});

// =======================
// Domain Layer
// =======================

$container->set(RankingService::class, function ($c) {
    return new RankingService(
        $c->get(TimeServiceInterface::class)
    );
});

$container->set(FlexService::class, function () {
    return new FlexService();
});

$container->set(SelectionService::class, function ($c) {
    return new SelectionService(
        $c->get(RankingService::class),
        $c->get(FlexService::class)
    );
});

$container->set(AssignmentService::class, function () {
    return new AssignmentService();
});

// =======================
// Application Layer - Use Cases
// =======================

// Boat Use Cases
$container->set(\App\Application\UseCase\Boat\RegisterBoatUseCase::class, function ($c) {
    return new \App\Application\UseCase\Boat\RegisterBoatUseCase(
        $c->get(BoatRepositoryInterface::class)
    );
});

$container->set(\App\Application\UseCase\Boat\UpdateBoatAvailabilityUseCase::class, function ($c) {
    return new \App\Application\UseCase\Boat\UpdateBoatAvailabilityUseCase(
        $c->get(BoatRepositoryInterface::class)
    );
});

// Crew Use Cases
$container->set(\App\Application\UseCase\Crew\RegisterCrewUseCase::class, function ($c) {
    return new \App\Application\UseCase\Crew\RegisterCrewUseCase(
        $c->get(CrewRepositoryInterface::class),
        $c->get(TimeServiceInterface::class)
    );
});

$container->set(\App\Application\UseCase\Crew\UpdateCrewAvailabilityUseCase::class, function ($c) {
    return new \App\Application\UseCase\Crew\UpdateCrewAvailabilityUseCase(
        $c->get(CrewRepositoryInterface::class)
    );
});

$container->set(\App\Application\UseCase\Crew\GetUserAssignmentsUseCase::class, function ($c) {
    return new \App\Application\UseCase\Crew\GetUserAssignmentsUseCase(
        $c->get(CrewRepositoryInterface::class),
        $c->get(EventRepositoryInterface::class),
        $c->get(SeasonRepositoryInterface::class)
    );
});

// Event Use Cases
$container->set(\App\Application\UseCase\Event\GetAllEventsUseCase::class, function ($c) {
    return new \App\Application\UseCase\Event\GetAllEventsUseCase(
        $c->get(EventRepositoryInterface::class)
    );
});

$container->set(\App\Application\UseCase\Event\GetEventUseCase::class, function ($c) {
    return new \App\Application\UseCase\Event\GetEventUseCase(
        $c->get(EventRepositoryInterface::class),
        $c->get(SeasonRepositoryInterface::class)
    );
});

// Season Use Cases
$container->set(\App\Application\UseCase\Season\ProcessSeasonUpdateUseCase::class, function ($c) {
    return new \App\Application\UseCase\Season\ProcessSeasonUpdateUseCase(
        $c->get(BoatRepositoryInterface::class),
        $c->get(CrewRepositoryInterface::class),
        $c->get(EventRepositoryInterface::class),
        $c->get(SeasonRepositoryInterface::class),
        $c->get(SelectionService::class),
        $c->get(AssignmentService::class)
    );
});

$container->set(\App\Application\UseCase\Season\GenerateFlotillaUseCase::class, function ($c) {
    return new \App\Application\UseCase\Season\GenerateFlotillaUseCase(
        $c->get(EventRepositoryInterface::class),
        $c->get(SeasonRepositoryInterface::class)
    );
});

$container->set(\App\Application\UseCase\Season\UpdateConfigUseCase::class, function ($c) {
    return new \App\Application\UseCase\Season\UpdateConfigUseCase(
        $c->get(SeasonRepositoryInterface::class)
    );
});

// Admin Use Cases
$container->set(\App\Application\UseCase\Admin\GetMatchingDataUseCase::class, function ($c) {
    return new \App\Application\UseCase\Admin\GetMatchingDataUseCase(
        $c->get(BoatRepositoryInterface::class),
        $c->get(CrewRepositoryInterface::class),
        $c->get(EventRepositoryInterface::class)
    );
});

$container->set(\App\Application\UseCase\Admin\SendNotificationsUseCase::class, function ($c) {
    return new \App\Application\UseCase\Admin\SendNotificationsUseCase(
        $c->get(EventRepositoryInterface::class),
        $c->get(SeasonRepositoryInterface::class),
        $c->get(EmailServiceInterface::class),
        $c->get(CalendarServiceInterface::class)
    );
});

// =======================
// Presentation Layer - Controllers
// =======================

$container->set(\App\Presentation\Controller\EventController::class, function ($c) {
    return new \App\Presentation\Controller\EventController(
        $c->get(\App\Application\UseCase\Event\GetAllEventsUseCase::class),
        $c->get(\App\Application\UseCase\Event\GetEventUseCase::class)
    );
});

$container->set(\App\Presentation\Controller\AvailabilityController::class, function ($c) {
    return new \App\Presentation\Controller\AvailabilityController(
        $c->get(\App\Application\UseCase\Boat\RegisterBoatUseCase::class),
        $c->get(\App\Application\UseCase\Boat\UpdateBoatAvailabilityUseCase::class),
        $c->get(\App\Application\UseCase\Crew\RegisterCrewUseCase::class),
        $c->get(\App\Application\UseCase\Crew\UpdateCrewAvailabilityUseCase::class),
        $c->get(\App\Application\UseCase\Season\ProcessSeasonUpdateUseCase::class)
    );
});

$container->set(\App\Presentation\Controller\AssignmentController::class, function ($c) {
    return new \App\Presentation\Controller\AssignmentController(
        $c->get(\App\Application\UseCase\Crew\GetUserAssignmentsUseCase::class)
    );
});

$container->set(\App\Presentation\Controller\AdminController::class, function ($c) {
    return new \App\Presentation\Controller\AdminController(
        $c->get(\App\Application\UseCase\Admin\GetMatchingDataUseCase::class),
        $c->get(\App\Application\UseCase\Admin\SendNotificationsUseCase::class),
        $c->get(\App\Application\UseCase\Season\UpdateConfigUseCase::class)
    );
});

return $container;
