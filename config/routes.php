<?php

declare(strict_types=1);

/**
 * Route Definitions
 *
 * Maps HTTP methods and paths to controller actions.
 * Uses simple pattern matching for route parameters.
 *
 * Route Format:
 * [
 *     'method' => 'GET|POST|PUT|PATCH|DELETE',
 *     'path' => '/api/endpoint',
 *     'controller' => ControllerClass::class,
 *     'action' => 'methodName',
 *     'auth' => true|false  // Whether authentication is required
 * ]
 */

use App\Presentation\Controller\EventController;
use App\Presentation\Controller\AvailabilityController;
use App\Presentation\Controller\AssignmentController;
use App\Presentation\Controller\AdminController;

return [
    // =======================
    // Public Endpoints
    // =======================

    [
        'method' => 'GET',
        'path' => '/api/events',
        'controller' => EventController::class,
        'action' => 'getAll',
        'auth' => false,
    ],

    [
        'method' => 'GET',
        'path' => '/api/events/{id}',
        'controller' => EventController::class,
        'action' => 'getOne',
        'auth' => false,
    ],

    // =======================
    // Authenticated Endpoints (Name-Based)
    // =======================

    // Register Boat
    [
        'method' => 'POST',
        'path' => '/api/boats/register',
        'controller' => AvailabilityController::class,
        'action' => 'registerBoat',
        'auth' => true,
    ],

    // Update Boat Availability
    [
        'method' => 'PATCH',
        'path' => '/api/boats/availability',
        'controller' => AvailabilityController::class,
        'action' => 'updateBoatAvailability',
        'auth' => true,
    ],

    // Register Crew
    [
        'method' => 'POST',
        'path' => '/api/crews/register',
        'controller' => AvailabilityController::class,
        'action' => 'registerCrew',
        'auth' => true,
    ],

    // Update Crew Availability
    [
        'method' => 'PATCH',
        'path' => '/api/users/me/availability',
        'controller' => AvailabilityController::class,
        'action' => 'updateCrewAvailability',
        'auth' => true,
    ],

    // Get User Assignments
    [
        'method' => 'GET',
        'path' => '/api/assignments',
        'controller' => AssignmentController::class,
        'action' => 'getUserAssignments',
        'auth' => true,
    ],

    // =======================
    // Admin Endpoints
    // =======================

    // Get Matching Data for Event
    [
        'method' => 'GET',
        'path' => '/api/admin/matching/{eventId}',
        'controller' => AdminController::class,
        'action' => 'getMatchingData',
        'auth' => true,  // TODO: Add admin-specific auth
    ],

    // Send Notifications
    [
        'method' => 'POST',
        'path' => '/api/admin/notifications/{eventId}',
        'controller' => AdminController::class,
        'action' => 'sendNotifications',
        'auth' => true,  // TODO: Add admin-specific auth
    ],

    // Update Configuration
    [
        'method' => 'PATCH',
        'path' => '/api/admin/config',
        'controller' => AdminController::class,
        'action' => 'updateConfig',
        'auth' => true,  // TODO: Add admin-specific auth
    ],
];
