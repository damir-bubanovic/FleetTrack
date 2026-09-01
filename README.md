# FleetTrack

## Overview

FleetTrack is a multi-tenant fleet management and GPS tracking platform
built with Laravel.

FleetTrack is the system of record for business entities such as
companies, fleets, users, drivers, vehicles, and devices. It integrates
with Traccar for GPS tracking, position history, and GPS trip detection.

The backend follows a layered architecture with thin controllers, Action
classes for application logic, Form Requests for validation, Policies
and team-aware permissions for authorization, API Resources for response
contracts, and dedicated services for Traccar integration.

------------------------------------------------------------------------

# Technology Stack

## Backend

-   PHP `^8.3`
-   Laravel `^13.17`
-   MySQL
-   Redis
-   Laravel Sail
-   Laravel Sanctum
-   Spatie Laravel Permission with Teams
-   Pest
-   PHPStan / Larastan
-   Laravel Pint

## External Services

-   Traccar Server
-   Traccar REST API

------------------------------------------------------------------------

# Current Backend Status

The core fleet-management backend and the initial tracking foundation
are implemented and tested.

## Authentication

-   API login
-   Authenticated user endpoint
-   Logout
-   Laravel Sanctum authentication
-   Protected API routes

Current authentication endpoints:

``` text
POST /api/v1/auth/login
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

## Authorization and Multi-Tenancy

-   Spatie Laravel Permission with Teams
-   Company-based tenant isolation
-   Policy-based authorization
-   Team-aware permission middleware
-   Visibility scopes
-   Super Administrator access
-   Company Administrator access
-   Tracking authorization through `tracking.view`

## Companies

-   CRUD
-   Validation
-   Policies
-   API Resources
-   Feature tests

## Fleets

-   CRUD
-   Company ownership
-   Tenant isolation
-   Policies
-   Feature tests

## Drivers

-   Company-scoped driver backend
-   Actions
-   Validation
-   API Resources
-   Authorization
-   Feature coverage

## Vehicles

-   CRUD
-   Fleet assignment
-   Company isolation
-   Validation
-   Authorization
-   Feature tests

## Devices

-   CRUD
-   Vehicle assignment
-   Company isolation
-   Validation
-   Authorization
-   Traccar synchronization
-   Feature tests

------------------------------------------------------------------------

# Traccar Integration

All Traccar HTTP communication is isolated behind dedicated integration
services.

Current core integration classes include:

-   `TraccarClient`
-   `TraccarDeviceService`
-   `PositionService`
-   `ReportService`
-   `DeviceData`

## Device Synchronization

Device lifecycle writes are synchronized asynchronously through Laravel
queues.

``` text
FleetTrack API
    ↓
Action
    ↓
Domain Event
    ↓
Listener
    ↓
Queue Job
    ↓
TraccarDeviceService
    ↓
Traccar REST API
```

Implemented synchronization includes:

-   Create device
-   Update device
-   Delete device
-   Store Traccar device ID
-   Track synchronization state/timestamp

## Tracking Reads

Tracking and report reads are synchronous because the API caller
requires the current Traccar result.

``` text
FleetTrack API
    ↓
Tracking Action
    ↓
PositionService / ReportService
    ↓
Traccar REST API
    ↓
API Resource
    ↓
JSON Response
```

Traccar is the source of truth for GPS position data and detected GPS
trips.

------------------------------------------------------------------------

# Tracking API

The current tracking API contains five endpoints.

## Live Positions

``` text
GET /api/v1/tracking/positions
```

Provides:

-   Latest positions for visible synchronized devices
-   Fleet filtering
-   Vehicle filtering
-   Combined filters
-   Company isolation
-   Online/offline status
-   Last-seen information

## Vehicle Live Position

``` text
GET /api/v1/tracking/vehicles/{vehicle}
```

Provides the latest Traccar position for a visible vehicle with a
synchronized device.

## Vehicle Position History

``` text
GET /api/v1/tracking/vehicles/{vehicle}/positions
```

Provides historical vehicle positions for a requested date range.

Current validation:

-   `from` required
-   `to` required
-   `to` must be after `from`
-   Maximum range: 7 days

## Vehicle Trip Summary

``` text
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
```

Calculates aggregate statistics over a requested position-history range.

Current output includes:

-   Position count
-   Start/end time
-   Duration in seconds
-   Distance in kilometers
-   Average sampled speed
-   Maximum speed
-   Moving time
-   Stopped time
-   Speed unit (`knots`)

This endpoint summarizes a selected range; it does not detect individual
trips.

## Vehicle Trip History

``` text
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

Returns trips detected by Traccar through `/reports/trips`.

FleetTrack applies tenant visibility and transforms the external report
into its API contract.

The normalized response includes:

-   Start/end times
-   Start/end coordinates
-   Distance in kilometers
-   Duration in seconds
-   Average speed
-   Maximum speed
-   Speed unit (`knots`)
-   Start/end addresses
-   Traccar device/driver references where available

FleetTrack intentionally delegates GPS trip detection to Traccar rather
than maintaining a competing detection algorithm.

------------------------------------------------------------------------

# Architecture

The application separates responsibilities into:

``` text
Routes
    ↓
Middleware
    ↓
Form Requests
    ↓
Controllers
    ↓
Policies / Permissions
    ↓
Actions
    ↓
Models / Services
    ↓
API Resources
```

Additional asynchronous integration components include:

-   Events
-   Listeners
-   Queue Jobs
-   DTOs

Controllers remain thin while Actions contain application/business
logic.

See `ARCHITECTURE.md` for the detailed architecture and integration
flows.

------------------------------------------------------------------------

# Multi-Tenancy

FleetTrack uses company-based tenancy.

Core rules:

-   Company users access only data visible to their company.
-   Super Administrators can have global visibility.
-   Team-aware permissions are configured with Spatie Permission.
-   Policies centralize authorization.
-   Visibility scopes restrict database queries.
-   Traccar data is exposed only after resolving an authorized
    FleetTrack entity.

External Traccar identifiers never bypass FleetTrack tenant
authorization.

------------------------------------------------------------------------

# Development

FleetTrack uses Laravel Sail.

## Start the environment

``` bash
sail up -d
```

If the `sail` shell alias is not configured:

``` bash
./vendor/bin/sail up -d
```

## Run the queue worker

``` bash
sail artisan queue:work
```

## Run the full test suite

``` bash
sail artisan test
```

## Run a targeted test

Example:

``` bash
sail artisan test tests/Feature/Api/Tracking/LiveTrackingControllerTest.php
```

## Static analysis

``` bash
sail composer types:check
```

## Formatting / lint

``` bash
sail composer lint
```

Before committing a completed functionality slice, the expected quality
checks are:

``` bash
sail composer lint
sail composer types:check
sail artisan test
```

------------------------------------------------------------------------

# Project Structure

``` text
app/
├── Actions/
├── Data/
├── Events/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Listeners/
├── Models/
├── Policies/
└── Services/
    └── Traccar/
```

Tests are organized under:

``` text
tests/
├── Feature/
└── Unit/
```

------------------------------------------------------------------------

# Development Principles

-   Thin controllers
-   Business logic in Actions
-   Validation in Form Requests
-   Policy/permission-based authorization
-   Strict tenant isolation
-   External APIs behind services
-   Asynchronous device synchronization
-   Synchronous tracking/report reads
-   Stable API Resource contracts
-   Feature coverage for new API behavior
-   PHPStan clean
-   Laravel Pint compliant
-   Focused Git commits

------------------------------------------------------------------------

# Documentation

Project documentation:

-   `README.md` --- project overview and development entry point
-   `FEATURES.md` --- implemented functionality and roadmap
-   `ARCHITECTURE.md` --- detailed current architecture
-   `AGENTS.md` --- contributor rules and fresh-session continuation
    context
-   `docs/ARCHITECTURE_DECISIONS.md` --- durable architectural decisions

The latest source code remains the primary source of truth if
documentation and implementation ever differ.

------------------------------------------------------------------------

# Roadmap

## Completed

-   Authentication
-   Authorization and multi-tenancy
-   Companies
-   Fleets
-   Drivers
-   Vehicles
-   Devices
-   Traccar device synchronization
-   Live Tracking
-   Online/offline tracking status
-   Vehicle position history
-   Vehicle aggregate trip summary
-   Traccar-detected vehicle trip history

## Remaining

1.  Geofences
2.  Alerts
3.  Reports
4.  Dashboard

The next module should be selected after reviewing requirements and the
latest project state.

------------------------------------------------------------------------

# Current Development Checkpoint

The latest completed functionality is the Traccar-backed vehicle
trip-history endpoint.

At this checkpoint:

-   All five tracking endpoints are implemented.
-   Tracking feature tests pass.
-   The full test suite passes.
-   PHPStan / Larastan reports no errors.
-   Formatting/lint checks pass.
-   The latest Trips functionality has been committed and pushed.

After the documentation cleanup, development can continue with the next
roadmap module without rebuilding the completed Live Tracking or Trips
foundation.
