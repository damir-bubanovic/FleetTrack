# FleetTrack

## Overview

FleetTrack is a multi-tenant fleet management and GPS tracking platform
built with Laravel.

FleetTrack is the system of record for business entities such as
companies, fleets, users, drivers, vehicles, devices, geofences, alerts,
and custom alert rules. It integrates with Traccar for GPS tracking,
position history, GPS trip detection, device and geofence
synchronization, device/geofence permission associations, and tracking
events.

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

The core fleet-management backend, tracking foundation, Geofence module,
Alerts module, expanded Reports read API, and Dashboard overview API are
implemented and tested.

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
-   Geofence authorization through `geofences.*`
-   Alert authorization through `alerts.*`
-   Alert Rule authorization through `alert-rules.*`
-   Report authorization through `reports.view`
-   Report export permission reserved through `reports.export`

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
-   Many-to-many Geofence associations

## Devices

-   CRUD
-   Vehicle assignment
-   Company isolation
-   Validation
-   Authorization
-   Traccar synchronization
-   Association reconciliation after Traccar synchronization
-   Feature and job tests

## Geofences

The Geofence module is implemented, including local lifecycle
management, Traccar synchronization, Vehicle associations, and Traccar
entry/exit event handling.

Implemented:

-   Full CRUD API
-   Company ownership and tenant isolation
-   Validation
-   Policy/permission authorization
-   API Resource contract
-   Local `Geofence` model and factory
-   Traccar geofence ID and synchronization timestamp
-   Asynchronous create/update/delete synchronization with Traccar
-   Many-to-many Geofence ↔ Vehicle association
-   Tenant-safe attach/detach API
-   Idempotent attach/detach behavior
-   Traccar device/geofence permission synchronization
-   Protection against stale attach/detach queue jobs
-   Reconciliation after a Geofence receives its Traccar ID
-   Reconciliation after a Device receives its Traccar ID
-   Secure Traccar geofence entry/exit webhook handling
-   Application events for geofence transitions
-   Geofence alert generation
-   Focused API, Action, relationship, service, queue-job, listener, and
    webhook tests

Current Geofence API includes CRUD plus Vehicle association endpoints:

``` text
GET        /api/v1/geofences
POST       /api/v1/geofences
GET        /api/v1/geofences/{geofence}
PUT/PATCH  /api/v1/geofences/{geofence}
DELETE     /api/v1/geofences/{geofence}

POST       /api/v1/geofences/{geofence}/vehicles/{vehicle}
DELETE     /api/v1/geofences/{geofence}/vehicles/{vehicle}
```

## Alerts

The backend Alerts module is implemented for the currently defined alert
sources.

Implemented alert sources:

-   Geofence entry
-   Geofence exit
-   Overspeed
-   Ignition on
-   Ignition off
-   Device offline

Implemented alert capabilities:

-   Secure Traccar event ingestion
-   Event-specific DTOs and handlers
-   Domain events and queued listeners
-   Persistent alert history
-   Company/vehicle association
-   Severity
-   Alert acknowledgement
-   Tenant-safe list/show API
-   `alerts.view` and `alerts.acknowledge` permissions
-   Custom Alert Rule CRUD
-   Company-wide and vehicle-specific Alert Rules
-   Active/inactive rules
-   Configurable severity
-   Overspeed threshold conditions
-   Vehicle-specific rule precedence with company-wide fallback
-   Default alert behavior when no custom rule matches
-   Focused API, Action, Policy, Model, Request, resolver, listener, and
    Traccar event tests

Current Alert API:

``` text
GET   /api/v1/alerts
GET   /api/v1/alerts/{alert}
PATCH /api/v1/alerts/{alert}/acknowledge
```

Custom Alert Rules are exposed through:

``` text
GET        /api/v1/alert-rules
POST       /api/v1/alert-rules
GET        /api/v1/alert-rules/{alertRule}
PUT/PATCH  /api/v1/alert-rules/{alertRule}
DELETE     /api/v1/alert-rules/{alertRule}
```

## Reports

Reports reuse the existing tracking application layer and Traccar
service boundary.

Current Reports API:

``` text
GET /api/v1/reports/vehicles/{vehicle}/trips
GET /api/v1/reports/vehicles/{vehicle}/trip-summary
GET /api/v1/reports/vehicles/{vehicle}/stops
GET /api/v1/reports/vehicles/{vehicle}/events
GET /api/v1/reports/vehicles/{vehicle}/route
GET /api/v1/reports/vehicles/{vehicle}/summary
GET /api/v1/reports/vehicles/{vehicle}/hours
GET /api/v1/reports/vehicles/{vehicle}/combined
```

Implemented report behavior includes:

-   Authenticated, team-aware routes
-   `reports.view` authorization
-   Required `from`/`to` date range with ordering validation
-   Tenant-safe Vehicle/Device resolution
-   Super Administrator access
-   Trips, stops, events, route, summary, hours, and combined Traccar
    report reads
-   FleetTrack aggregate trip-summary reporting
-   Normalized Resources where a stable FleetTrack contract exists
-   Raw combined-report payload preservation
-   Missing/unsynchronized Device handling
-   Focused feature coverage

`reports.export` is reserved but export/report generation is not
implemented because the output contract has not been defined.

## Dashboard

Dashboard overview endpoint:

``` text
GET /api/v1/dashboard/overview
```

The response exposes tenant-aware operational metrics for:

-   Companies
-   Fleets
-   Vehicles
-   Devices
-   Online Vehicles
-   Offline Vehicles
-   Offline synchronized Devices
-   Total Alerts
-   Unacknowledged Alerts

Online/offline calculations reuse the tracking freshness rule. An
unassigned synchronized Device can contribute to Device connectivity
without being counted as an online Vehicle. Unsynchronized Devices are
not counted as offline Devices.

The current metrics are the initial fleet KPI set. Time-based KPIs
should only be added when their reporting period and aggregation rules
are defined.

# Traccar Integration

All Traccar HTTP communication is isolated behind dedicated integration
services.

Current core integration classes include:

-   `TraccarClient`
-   `TraccarDeviceService`
-   `TraccarGeofenceService`
-   `PositionService`
-   `ReportService`
-   `DeviceData`
-   `GeofenceData`
-   Event DTOs for geofence, overspeed, ignition, and device-offline
    events

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
-   Reconcile existing Geofence associations after initial Device
    synchronization

## Geofence Synchronization

Geofence lifecycle writes follow the same asynchronous integration
pattern.

``` text
FleetTrack API
    ↓
Geofence Action
    ↓
Geofence Event
    ↓
Listener
    ↓
Queue Job
    ↓
TraccarGeofenceService
    ↓
Traccar REST API
```

Implemented synchronization includes:

-   Create Geofence in Traccar
-   Update Geofence in Traccar
-   Delete Geofence from Traccar
-   Store Traccar geofence ID
-   Track synchronization timestamp
-   Reconcile existing Vehicle associations after initial Geofence
    synchronization

## Geofence ↔ Vehicle Permission Synchronization

FleetTrack stores the business-domain association as Geofence ↔ Vehicle.
It does not expose a Traccar-specific Geofence ↔ Device relationship as
the CRM domain model.

For Traccar synchronization, FleetTrack resolves:

``` text
Geofence
    ↓
traccar_geofence_id

Vehicle
    ↓
Device
    ↓
traccar_device_id
```

The resulting Traccar permission is synchronized through
`/api/permissions`.

Queue jobs re-check current local state before writing to Traccar. This
prevents stale queued attach/detach jobs from overwriting a newer
association state.

Because Device and Geofence creation are asynchronous, association
synchronization is reconciled when either side later receives its
Traccar ID.

## Traccar Event Ingestion

FleetTrack exposes a webhook endpoint for supported Traccar events:

``` text
POST /api/v1/traccar/events
```

The endpoint is protected by `VerifyTraccarWebhook` rather than Sanctum
because it is called by Traccar.

Supported event types currently include:

-   `geofenceEnter`
-   `geofenceExit`
-   `deviceOverspeed`
-   `ignitionOn`
-   `ignitionOff`
-   `deviceOffline`

Incoming payloads are validated and translated into application-specific
DTOs/events before alert creation.

## Tracking and Report Reads

Tracking and report reads are synchronous because the API caller
requires the current Traccar result.

``` text
FleetTrack API
    ↓
Tracking / Report Action
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

The tracking API contains five endpoints.

## Live Positions

``` text
GET /api/v1/tracking/positions
```

Provides latest positions for visible synchronized devices, filtering,
tenant isolation, online/offline status, and last-seen information.

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

Current validation includes required `from` and `to`, `to` after `from`,
and a maximum seven-day range.

## Vehicle Trip Summary

``` text
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
```

Calculates aggregate statistics over a requested position-history range,
including position count, start/end time, duration, distance,
average/max speed, moving time, stopped time, and speed unit.

This endpoint summarizes a selected position range; it does not detect
individual trips.

## Vehicle Trip History

``` text
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

Returns trips detected by Traccar through `/reports/trips`.

FleetTrack applies tenant visibility and transforms the external report
into its API contract. FleetTrack intentionally delegates GPS trip
detection to Traccar rather than maintaining a competing detection
algorithm.

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

Additional asynchronous integration components include Events,
Listeners, Queue Jobs, and DTOs.

Controllers remain thin while Actions contain application/business
logic.

See `ARCHITECTURE.md` for detailed architecture, synchronization flows,
event handling, and eventual-consistency rules.

------------------------------------------------------------------------

# Multi-Tenancy

FleetTrack uses company-based tenancy.

Core rules:

-   Company users access only data visible to their company.
-   Super Administrators can have global visibility.
-   Team-aware permissions are configured with Spatie Permission.
-   Policies centralize authorization where model authorization is
    required.
-   Permission gates protect capability-specific endpoints such as
    tracking and reports.
-   Visibility scopes restrict database queries.
-   Cross-company Geofence ↔ Vehicle associations are rejected.
-   Traccar data is exposed only after resolving an authorized
    FleetTrack entity.
-   Alert Rules and Alerts remain company-scoped.
-   External Traccar identifiers never bypass FleetTrack tenant
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

## Run a targeted test

Example:

``` bash
sail artisan test tests/Feature/Report/VehicleTripReportApiTest.php
```

## Formatting

Apply Laravel Pint formatting:

``` bash
sail composer lint
```

Check formatting without modifying files:

``` bash
sail composer lint:check
```

## Static analysis

``` bash
sail composer types:check
```

## Full test suite

``` bash
sail artisan test
```

Before committing a meaningful functionality slice, the expected quality
gate is:

``` bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

All failures should be fixed before committing the slice.

------------------------------------------------------------------------

# Project Structure

``` text
app/
├── Actions/
├── Data/
├── Events/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Listeners/
├── Models/
├── Policies/
└── Services/
    └── Traccar/
```

Tests are primarily organized under feature-oriented paths, including
API and focused feature tests for Actions, Models, Policies, Requests,
Listeners, Jobs, Services, Traccar events, Alert Rules, and Reports.

------------------------------------------------------------------------

# Development Principles

-   Thin controllers
-   Business logic in Actions
-   Validation in Form Requests
-   Policy/permission-based authorization
-   Strict tenant isolation
-   External APIs behind services
-   Asynchronous Device and Geofence synchronization
-   Event/listener/job integration flows
-   Re-check current local state in asynchronous association jobs
-   Reconcile asynchronous dependencies when external IDs become
    available
-   Secure external webhook ingestion
-   Synchronous tracking/report reads
-   Stable API Resource contracts
-   Reuse existing application/domain logic instead of duplicating it
    for Reports
-   Focused tests for new behavior
-   PHPStan clean
-   Laravel Pint compliant
-   Full test suite green before meaningful commits
-   Focused Git commits

------------------------------------------------------------------------

# Documentation

Project documentation:

-   `README.md` --- project overview and development entry point
-   `FEATURES.md` --- implemented functionality, current module status,
    and roadmap
-   `ARCHITECTURE.md` --- detailed current architecture and integration
    flows
-   `AGENTS.md` --- contributor rules and fresh-session continuation
    context
-   `docs/ARCHITECTURE_DECISIONS.md` --- durable architectural decisions

The latest source code remains the primary source of truth if
documentation and implementation ever differ.

------------------------------------------------------------------------

# Roadmap

## Completed Foundations and Modules

-   Authentication
-   Authorization and multi-tenancy
-   Companies
-   Fleets
-   Drivers
-   Vehicles
-   Devices
-   Traccar Device synchronization
-   Live Tracking and online/offline status
-   Vehicle position history
-   Vehicle aggregate trip summary
-   Traccar-detected vehicle trip history
-   Geofence CRUD, synchronization, associations, and Traccar permission
    synchronization
-   Secure supported Traccar event ingestion
-   Alerts and acknowledgement
-   Custom Alert Rule management and runtime evaluation
-   Reports read API: trips, trip summary, stops, events, route,
    summary, hours, and combined
-   Dashboard overview and initial fleet KPI metrics

## Remaining Product Decisions

-   Define report export/report-generation format before implementing
    `reports.export`
-   Define a reporting period and aggregation rules before adding
    time-based Dashboard KPIs
-   Add external Alert delivery only when channel/recipient/retry
    requirements are defined
-   Introduce a persistent FleetTrack Trip entity only if FleetTrack
    needs to own Trip-specific business data

The current backend should otherwise move through final integration and
documentation hardening rather than adding speculative behavior.

# Current Development Checkpoint

The latest source snapshot includes the expanded Reports API and
Dashboard overview.

At this checkpoint:

-   Reports exposes eight vehicle report endpoints.
-   Report reads remain behind existing tracking Actions and
    `ReportService`.
-   Dashboard overview exposes fleet totals, vehicle connectivity,
    synchronized-Device offline counts, and Alert summary metrics.
-   Tenant isolation and Super Administrator behavior are covered.
-   Vehicle and Device connectivity semantics are separated for
    unassigned Devices.
-   The latest full quality gate was green:

``` bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

The remaining explicit Reports capability is export/report generation
through `reports.export`; its format has not been defined.

Use the source code as the final authority and do not add undefined
export, notification-delivery, persistent Trip, or time-based Dashboard
behavior without a concrete product contract.
