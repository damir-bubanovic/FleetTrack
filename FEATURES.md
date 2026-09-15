# FleetTrack Features

## Overview

This document tracks the functional capabilities currently implemented
in FleetTrack and the remaining product roadmap.

The latest completed backend checkpoint includes the core
fleet-management modules, Traccar tracking integration, the completed
Geofence module, the completed backend Alerts module with custom Alert
Rules, and the first dedicated Reports API slice.

The current active development area is **Reports**. The latest committed
Reports functionality is a vehicle trip report endpoint that reuses the
existing Traccar trip-reporting infrastructure.

------------------------------------------------------------------------

# Implemented Features

## Authentication

-   API authentication with Laravel Sanctum
-   Login
-   Authenticated user endpoint
-   Logout
-   Personal access tokens
-   Protected API routes

## Authorization and Multi-Tenancy

-   Spatie Laravel Permission
-   Teams for company-based permissions
-   Policy-based authorization
-   Company-scoped data access
-   `SetPermissionTeam` middleware
-   Super Administrator access
-   Company Administrator access
-   Fleet Manager access
-   Driver role support
-   Tenant isolation tests
-   Tracking authorization through `tracking.view`
-   Geofence authorization through `geofences.*`
-   Alert authorization through `alerts.*`
-   Alert Rule authorization through `alert-rules.*`
-   Report authorization through `reports.view`
-   Report export permission reserved through `reports.export`

## Companies

-   List companies
-   Create company
-   View company
-   Update company
-   Delete company
-   Validation
-   API Resources
-   Authorization
-   Feature tests

## Fleets

-   Full CRUD
-   Company ownership
-   Company isolation
-   Policies
-   Validation
-   Feature tests

## Users

-   Company users
-   Super Administrator role
-   Company Administrator role
-   Fleet Manager role
-   Driver role
-   Team-aware permissions

## Drivers

-   Driver backend module
-   Company ownership and isolation
-   Actions
-   Requests and API Resources
-   Authorization
-   Feature coverage

## Vehicles

-   Full CRUD
-   Company isolation
-   Fleet assignment
-   Validation
-   Authorization
-   Feature tests
-   Many-to-many Geofence associations

## Devices

-   Full CRUD
-   Vehicle assignment
-   Company isolation
-   Validation
-   Authorization
-   Traccar synchronization state
-   Traccar device ID storage
-   Synchronization timestamp tracking
-   Queue-based Traccar synchronization
-   Geofence permission reconciliation after synchronization
-   Feature and queue-job coverage

------------------------------------------------------------------------

# Traccar Integration

## Core Integration

-   `TraccarClient`
-   `TraccarDeviceService`
-   `TraccarGeofenceService`
-   `PositionService`
-   `ReportService`
-   `DeviceData` DTO
-   `GeofenceData` DTO
-   Event DTOs for supported Traccar events
-   Centralized Traccar HTTP communication

## Device Synchronization

FleetTrack synchronizes device lifecycle changes to Traccar.

Implemented:

-   Create device in Traccar
-   Update device in Traccar
-   Delete device from Traccar
-   Store Traccar device ID
-   Track synchronization state/timestamp
-   Queue-based synchronization
-   Events
-   Listeners
-   Queue Jobs
-   Retryable integration flow
-   Reconcile existing Geofence associations after initial Device
    synchronization

Device synchronization writes follow:

``` text
Action
→ Event
→ Listener
→ Queue Job
→ Traccar service
→ Traccar REST API
```

## Geofence Synchronization

FleetTrack synchronizes Geofence lifecycle changes to Traccar.

Implemented:

-   Create Geofence in Traccar
-   Update Geofence in Traccar
-   Delete Geofence from Traccar
-   Store Traccar geofence ID
-   Track synchronization timestamp
-   Queue-based synchronization
-   Events
-   Listeners
-   Queue Jobs
-   Retryable integration flow
-   Reconcile existing Vehicle associations after initial Geofence
    synchronization

## Geofence ↔ Vehicle Permission Synchronization

FleetTrack models the business-domain association as Geofence ↔ Vehicle.

For Traccar synchronization, FleetTrack resolves the Vehicle's assigned
Device and uses the Device's `traccar_device_id` together with the
Geofence's `traccar_geofence_id`.

Implemented:

-   Traccar permission association
-   Traccar permission disassociation
-   Queue-based permission synchronization
-   Stale attach-job protection
-   Stale detach-job protection
-   Reconciliation when the Geofence receives its Traccar ID
-   Reconciliation when the Device receives its Traccar ID

## Traccar Event Ingestion

Endpoint:

``` text
POST /api/v1/traccar/events
```

The endpoint is protected by `VerifyTraccarWebhook` rather than Sanctum
because it is called by Traccar.

Supported event types:

-   `geofenceEnter`
-   `geofenceExit`
-   `deviceOverspeed`
-   `ignitionOn`
-   `ignitionOff`
-   `deviceOffline`

Implemented:

-   Webhook token verification
-   Event-specific payload validation
-   Event DTOs
-   Event handlers
-   Application/domain events
-   Queued alert listeners
-   Tenant-aware FleetTrack entity resolution
-   Focused webhook and event tests

## Tracking and Report Reads

Tracking and report data is read synchronously through the Traccar
service layer because the API caller requires the current result.

Implemented services include:

-   Latest position retrieval through `PositionService`
-   Historical position retrieval through `PositionService`
-   Detected trip reports through `ReportService`

------------------------------------------------------------------------

# Live Tracking

Live Tracking is implemented.

## Fleet-Wide Live Positions

Endpoint:

``` text
GET /api/v1/tracking/positions
```

Capabilities:

-   Latest positions for visible synchronized devices
-   Company tenant isolation
-   Super Administrator visibility
-   Fleet filtering
-   Vehicle filtering
-   Combined fleet and vehicle filtering
-   Unsynced devices excluded
-   Device and vehicle context in API response

## Per-Vehicle Live Position

Endpoint:

``` text
GET /api/v1/tracking/vehicles/{vehicle}
```

Capabilities:

-   Latest position for a specific vehicle
-   Company isolation
-   Synced-device resolution
-   Not-found behavior when no current Traccar position exists

## Online / Offline Status

Live position responses include vehicle tracking status.

Implemented:

-   Online status for recent GPS fixes
-   Offline status for stale GPS fixes
-   Offline status when GPS fix time is missing
-   Last-seen timestamp
-   Current freshness threshold based on latest GPS fix

------------------------------------------------------------------------

# Vehicle Position History

Endpoint:

``` text
GET /api/v1/tracking/vehicles/{vehicle}/positions
```

Implemented:

-   Historical positions for a vehicle
-   `from` and `to` date range
-   Date-order validation
-   Maximum 7-day range
-   Exactly 7 days allowed
-   More than 7 days rejected
-   Company tenant isolation
-   Unsynced vehicle handling
-   Traccar query verification
-   Historical position API Resource

------------------------------------------------------------------------

# Vehicle Trip Summary

Endpoint:

``` text
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
```

This endpoint calculates an aggregate summary over the explicitly
requested vehicle position-history range.

Implemented metrics:

-   Position count
-   Start time
-   End time
-   Duration in seconds
-   Distance in kilometers
-   Average sampled speed
-   Maximum speed
-   Moving time in seconds
-   Stopped time in seconds
-   Explicit speed unit (`knots`)

Additional behavior:

-   Empty-history summary
-   Missing speed-data handling
-   Company tenant isolation
-   Shared historical date-range validation

This is an aggregate range summary and is separate from Traccar's
detected trip history.

------------------------------------------------------------------------

# Vehicle Trip History

Endpoint:

``` text
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

Trip detection is delegated to Traccar.

FleetTrack uses Traccar's `/reports/trips` report rather than
implementing a competing GPS trip-detection algorithm.

Implemented:

-   Traccar-detected trip history
-   Vehicle-to-synchronized-device resolution
-   Company tenant isolation
-   Empty report handling
-   Unsynced device handling
-   Date-range validation
-   Traccar request parameter verification
-   Normalized FleetTrack API Resource

Normalized trip response includes:

-   Traccar device reference
-   Driver unique reference when available
-   Start time
-   End time
-   Start coordinates
-   End coordinates
-   Distance in kilometers
-   Duration in seconds
-   Average speed
-   Maximum speed
-   Speed unit (`knots`)
-   Start address
-   End address

Traccar remains the source of truth for GPS trip detection.

------------------------------------------------------------------------

# Geofences

The backend Geofence module is complete for the currently defined
requirements.

## Geofence CRUD and Authorization

Endpoints:

``` text
GET    /api/v1/geofences
POST   /api/v1/geofences
GET    /api/v1/geofences/{geofence}
PUT    /api/v1/geofences/{geofence}
PATCH  /api/v1/geofences/{geofence}
DELETE /api/v1/geofences/{geofence}
```

Implemented:

-   Full Geofence CRUD
-   Company ownership and tenant isolation
-   Policy and permission-based authorization
-   Validation through Form Requests
-   API Resource responses
-   Protection of Traccar-managed fields from client writes
-   Company assignment enforcement for non-super-admin users
-   Feature coverage for CRUD and tenant boundaries

## Geofence Traccar Synchronization

Implemented:

-   Create Geofence in Traccar
-   Update Geofence in Traccar
-   Delete Geofence from Traccar
-   Store Traccar geofence ID
-   Track geofence synchronization timestamp
-   Event/listener/queue-job synchronization flow
-   Retryable synchronization jobs
-   Dedicated Traccar geofence DTO/service coverage

## Geofence Vehicle Associations

Endpoints:

``` text
POST   /api/v1/geofences/{geofence}/vehicles/{vehicle}
DELETE /api/v1/geofences/{geofence}/vehicles/{vehicle}
```

Implemented:

-   Many-to-many Geofence ↔ Vehicle relationship
-   `geofence_vehicle` pivot table with duplicate protection
-   Same-company association enforcement
-   Idempotent attach and detach operations
-   Company-admin authorization through the Geofence update policy
-   Association events and listeners
-   Queue-based Traccar permission synchronization
-   Traccar `POST /permissions` association
-   Traccar `DELETE /permissions` disassociation
-   Stale attach-job protection when an association has been removed
-   Stale detach-job protection when an association has been recreated
-   Reconciliation after a Geofence receives its Traccar ID
-   Reconciliation after a Device receives its Traccar ID
-   Dedicated action, API, service, relationship, and job tests

## Geofence Event Handling

Implemented:

-   Secure Traccar webhook ingestion
-   `geofenceEnter` handling
-   `geofenceExit` handling
-   Geofence event DTO
-   Event handler
-   `GeofenceTransitionOccurred` application event
-   Queued alert creation
-   Tenant-safe Device/Vehicle/Geofence resolution
-   Focused event and webhook coverage

No email, push, or SMS delivery behavior is currently required by the
implemented product requirements. Such delivery should be added only
when explicit requirements define it.

------------------------------------------------------------------------

# Alerts

The backend Alerts module is implemented for the currently defined alert
sources and management requirements.

## Supported Alert Sources

-   Geofence entry
-   Geofence exit
-   Overspeed
-   Ignition on
-   Ignition off
-   Device offline

## Alert History and Acknowledgement

Endpoints:

``` text
GET   /api/v1/alerts
GET   /api/v1/alerts/{alert}
PATCH /api/v1/alerts/{alert}/acknowledge
```

Implemented:

-   Persistent Alert model
-   Company and Vehicle association
-   Alert type
-   Severity
-   Alert message
-   Traccar event idempotency
-   Alert listing
-   Individual Alert retrieval
-   Alert acknowledgement
-   Idempotent acknowledgement behavior
-   Company tenant isolation
-   `alerts.view` permission
-   `alerts.acknowledge` permission
-   Policy authorization
-   API Resource
-   Focused API and Action tests

## Overspeed Alerts

Implemented:

-   `deviceOverspeed` Traccar event handling
-   Overspeed event DTO
-   Overspeed application event
-   Queued alert listener
-   Knots-to-km/h conversion for alert messaging and rule evaluation
-   Default `warning` severity
-   Traccar event idempotency
-   Custom Alert Rule severity override
-   Custom speed-threshold evaluation

## Ignition Alerts

Implemented:

-   `ignitionOn`
-   `ignitionOff`
-   Ignition event DTO
-   Ignition application event
-   Queued alert listener
-   `ignition_on` and `ignition_off` alert types
-   Default `info` severity
-   Custom Alert Rule severity override

## Device Offline Alerts

Implemented:

-   `deviceOffline`
-   Device-offline event DTO
-   Device-offline application event
-   Queued alert listener
-   `device_offline` alert type
-   Default `warning` severity
-   Custom Alert Rule severity override

## Geofence Alerts

Implemented:

-   Geofence entry alerts
-   Geofence exit alerts
-   Queued alert generation from Geofence transition events
-   Default `info` severity
-   Custom Alert Rule severity override

------------------------------------------------------------------------

# Custom Alert Rules

Custom Alert Rule management and runtime evaluation are implemented.

Endpoints:

``` text
GET        /api/v1/alert-rules
POST       /api/v1/alert-rules
GET        /api/v1/alert-rules/{alertRule}
PUT/PATCH  /api/v1/alert-rules/{alertRule}
DELETE     /api/v1/alert-rules/{alertRule}
```

## Alert Rule Management

Implemented:

-   Company-scoped Alert Rules
-   Optional Vehicle scope
-   Company-wide rules when `vehicle_id` is null
-   Vehicle-specific rules when `vehicle_id` is set
-   Rule name
-   Alert type
-   Severity
-   JSON conditions
-   Active/inactive state
-   Company tenant isolation
-   Cross-company Vehicle protection
-   Super Administrator company handling
-   `alert-rules.view`
-   `alert-rules.create`
-   `alert-rules.update`
-   `alert-rules.delete`
-   Policies
-   Form Requests
-   Actions
-   API Resource
-   Model, Request, Policy, Action, and API tests

Supported rule types:

-   `overspeed`
-   `geofence_enter`
-   `geofence_exit`
-   `ignition_on`
-   `ignition_off`
-   `device_offline`

Supported severity values:

-   `info`
-   `warning`
-   `critical`

## Runtime Alert Rule Evaluation

Implemented:

-   Active rules only
-   Company matching
-   Alert-type matching
-   Vehicle-specific rules ordered before company-wide rules
-   Company-wide fallback when a vehicle-specific rule does not match
-   Overspeed threshold through `conditions.speed_limit_kmh`
-   Strict overspeed comparison: actual speed must be greater than the
    configured limit
-   Non-overspeed event rules require no additional condition
-   Default alert behavior preserved when no custom rule matches
-   Matching rule severity overrides the default alert severity
-   Runtime integration into:
    -   Overspeed alerts
    -   Ignition alerts
    -   Device-offline alerts
    -   Geofence alerts
-   Dedicated resolver tests
-   Alert Action integration tests

------------------------------------------------------------------------

# Reports

Reports is the current active development module.

The Reports API is designed as a reporting layer over existing
FleetTrack tracking/application capabilities. It should reuse existing
Actions, services, and API Resources rather than duplicating tracking
logic.

## Vehicle Trip Report

Endpoint:

``` text
GET /api/v1/reports/vehicles/{vehicle}/trips?from=<datetime>&to=<datetime>
```

Implemented:

-   Dedicated Reports controller
-   Dedicated report Form Request
-   Required `from` date
-   Required `to` date
-   `to` must be after `from`
-   Authenticated API route
-   `SetPermissionTeam` middleware
-   `reports.view` authorization
-   Reuse of `GetVehicleTrips`
-   Reuse of `ReportService`
-   Reuse of `VehicleTripResource`
-   Vehicle-to-visible-device resolution
-   Company tenant isolation
-   Empty Traccar report handling
-   Unsynced Device handling
-   Traccar request parameter verification
-   Normalized trip output
-   No direct Traccar HTTP logic in the Reports controller
-   Eight passing focused feature tests

The complete project quality gate passed after this slice:

``` text
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

The slice was committed as:

``` text
feat: add vehicle trip report endpoint
```

## Reports Work Remaining

The Reports module is not yet complete.

The next report functionality should be selected from the existing
product and tracking capabilities without duplicating Traccar
functionality.

Likely next slices include:

-   Report-oriented vehicle trip summary
-   Additional report metrics where required
-   Export/report generation using `reports.export`
-   Report-specific tests and tenant isolation
-   Final Reports integration verification

Exact report/export formats should be defined before implementation
rather than invented prematurely.

------------------------------------------------------------------------

# Dashboard

Dashboard development has not started.

Planned areas include:

-   Fleet overview
-   Active/online vehicles
-   Offline devices
-   Alerts summary
-   Fleet KPIs

Dashboard work should begin after the Reports module is completed.

------------------------------------------------------------------------

# Technical Features

-   Laravel 13
-   PHP 8.3+
-   Laravel Sail development environment
-   Laravel Sanctum
-   Spatie Laravel Permission with Teams
-   Redis queues
-   Queue retry support
-   Event-driven Device and Geofence synchronization
-   Secure Traccar webhook event ingestion
-   Service layer for Traccar
-   DTO pattern
-   Action pattern
-   Form Requests
-   API Resources
-   Policies
-   Permission-based capability gates
-   Multi-tenant visibility scopes
-   Custom Alert Rule resolution
-   PHPStan / Larastan
-   Laravel Pint
-   Pest tests
-   Laravel HTTP fakes for Traccar integration tests

------------------------------------------------------------------------

# Future Product Decisions

The following should be designed only when product requirements require
them.

## Persistent FleetTrack Trip Entity

FleetTrack does not currently need to persist Traccar-detected trips as
its own business entity.

A FleetTrack `Trip` model may become appropriate if trips require
application-owned data such as:

-   Notes
-   Approval workflows
-   Billing
-   Corrections
-   Audit history
-   Business classifications

If introduced, it should build on Traccar-detected GPS trips rather than
creating a second GPS trip-detection algorithm.

## Stops

Traccar supports stop reporting, but a dedicated FleetTrack Stops
API/module has not yet been implemented.

Add it when required by the product roadmap.

## External Alert Delivery

Persistent in-app Alert records and acknowledgement are implemented.

Email, push, SMS, or other external notification delivery should be
designed only if product requirements explicitly require those channels.

------------------------------------------------------------------------

# Current Quality Status

At the latest completed checkpoint:

-   Authentication and authorization foundation implemented
-   Core Company/Fleet/Driver/Vehicle/Device backend implemented
-   Traccar Device synchronization implemented
-   Live Tracking implemented
-   Vehicle position history implemented
-   Aggregate vehicle trip summary implemented
-   Traccar-detected trip history implemented
-   Geofence CRUD and tenant authorization implemented
-   Traccar Geofence lifecycle synchronization implemented
-   Geofence ↔ Vehicle associations implemented
-   Traccar Device/Geofence permission synchronization implemented
-   Association reconciliation for synchronization-order gaps
    implemented
-   Secure Geofence entry/exit event handling implemented
-   Alert persistence and acknowledgement implemented
-   Overspeed alert handling implemented
-   Ignition alert handling implemented
-   Device-offline alert handling implemented
-   Geofence alert generation implemented
-   Custom Alert Rule CRUD implemented
-   Runtime Alert Rule evaluation integrated with alert generation
-   First Reports endpoint implemented
-   Vehicle trip report has eight passing focused feature tests
-   Full test suite passing at the latest checkpoint
-   PHPStan / Larastan clean
-   Formatting/lint checks passing
-   Latest completed slice committed as
    `feat: add vehicle trip report endpoint`

------------------------------------------------------------------------

# Next Development Point

Geofences and the currently defined backend Alerts functionality are
complete.

**Reports is the active module.**

Completed within Reports:

1.  Vehicle trip report endpoint
2.  `reports.view` authorization
3.  Date-range validation
4.  Reuse of existing `GetVehicleTrips` and `ReportService`
5.  Tenant-safe report access
6.  Focused feature coverage
7.  Full quality gate and commit

Development is intentionally paused immediately after this checkpoint.

When development resumes:

1.  Continue the remaining Reports functionality.
2.  Define and implement report export behavior using `reports.export`
    when the required output format is established.
3.  Complete Reports integration verification.
4.  Continue with Dashboard.
5.  Perform final documentation/integration hardening as required.

Before implementing each remaining area, review the latest project
source and confirm the required domain behavior without duplicating
capabilities already provided by Traccar or the existing tracking
endpoints.
