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
use App\Presentation\Controller\AuthController;
use App\Presentation\Controller\UserController;

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

    [
        'method' => 'GET',
        'path' => '/api/flotillas',
        'controller' => EventController::class,
        'action' => 'getAllFlotillas',
        'auth' => false,
    ],

    // =======================
    // Authentication Endpoints
    // =======================

    [
        'method' => 'POST',
        'path' => '/api/auth/register',
        'controller' => AuthController::class,
        'action' => 'register',
        'auth' => false,
    ],

    [
        'method' => 'POST',
        'path' => '/api/auth/login',
        'controller' => AuthController::class,
        'action' => 'login',
        'auth' => false,
    ],

    [
        'method' => 'GET',
        'path' => '/api/auth/session',
        'controller' => AuthController::class,
        'action' => 'getSession',
        'auth' => true,
    ],

    [
        'method' => 'POST',
        'path' => '/api/auth/logout',
        'controller' => AuthController::class,
        'action' => 'logout',
        'auth' => true,
    ],

    // =======================
    // User Profile Endpoints
    // =======================

    [
        'method' => 'GET',
        'path' => '/api/users/me',
        'controller' => UserController::class,
        'action' => 'getProfile',
        'auth' => true,
    ],

    [
        'method' => 'POST',
        'path' => '/api/users/me',
        'controller' => UserController::class,
        'action' => 'addProfile',
        'auth' => true,
    ],

    [
        'method' => 'PATCH',
        'path' => '/api/users/me',
        'controller' => UserController::class,
        'action' => 'updateProfile',
        'auth' => true,
    ],

    // =======================
    // Authenticated Endpoints (Name-Based)
    // =======================

    // Update Availability (auto-detects boat owner, crew, or both)
    [
        'method' => 'PATCH',
        'path' => '/api/users/me/availability',
        'controller' => AvailabilityController::class,
        'action' => 'updateAvailability',
        'auth' => true,
    ],

    // Get Crew Availability
    [
        'method' => 'GET',
        'path' => '/api/users/me/availability',
        'controller' => AvailabilityController::class,
        'action' => 'getCrewAvailability',
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
