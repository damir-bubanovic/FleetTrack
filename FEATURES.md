# FleetTrack Features

## Overview

This document tracks the functional capabilities currently implemented
in FleetTrack and the remaining product roadmap.

The current backend checkpoint includes the core fleet-management
modules, Traccar tracking integration, Geofences, Alerts with custom
Alert Rules, the expanded Reports API, and the Dashboard overview API.

Reports now exposes vehicle trips, trip summary, stops, events, route,
summary, hours, and combined report reads. Dashboard now exposes
tenant-aware fleet counts, vehicle connectivity, synchronized-device
offline counts, and Alert summary metrics.

The only explicitly reserved Reports capability that is not implemented
is export/report generation through `reports.export`; its output format
has not yet been defined.
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

The Reports API is implemented as a reporting layer over existing
FleetTrack tracking/application capabilities. It reuses existing
Actions, the Traccar `ReportService`, tenant-visible Device resolution,
and tracking Resources rather than creating a second tracking subsystem.

All current report endpoints require authentication, the
`SetPermissionTeam` middleware, `reports.view`, and a valid `from`/`to`
date range.

Current endpoints:

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

Implemented:

-   Vehicle trip report through Traccar `/reports/trips`
-   FleetTrack aggregate trip summary over the requested position range
-   Stop report through Traccar `/reports/stops`
-   Event report through Traccar `/reports/events`
-   Route report through Traccar `/reports/route`
-   Vehicle summary report through Traccar `/reports/summary`
-   Vehicle hours report through Traccar `/reports/hours`
-   Combined report through Traccar `/reports/combined`
-   Dedicated Reports controller
-   Shared report Form Request
-   Required `from` and `to` dates
-   `to` must be after `from`
-   `reports.view` authorization
-   Company tenant isolation
-   Super Administrator access
-   Missing/unsynchronized Device handling
-   Empty Traccar report handling
-   Traccar request parameter verification
-   API Resource normalization where a stable FleetTrack contract exists
-   Raw combined-report payload preservation where no narrower combined
    schema has been established
-   Focused feature coverage across the report endpoints

## Reports Work Remaining

Report export/report generation is not implemented.

The permission boundary already reserves:

``` text
reports.export
```

The exact export format, content, and validation contract must be
defined before implementation. FleetTrack should not invent
CSV/PDF/export behavior without that product requirement.

# Dashboard

Dashboard overview is implemented.

Endpoint:

``` text
GET /api/v1/dashboard/overview
```

Implemented metrics:

-   Visible Company count
-   Fleet count
-   Vehicle count
-   Device count
-   Online Vehicle count
-   Offline Vehicle count
-   Offline synchronized Device count
-   Total Alert count
-   Unacknowledged Alert count

Behavior:

-   Company-scoped overview for tenant users
-   Super Administrator overview across customer Companies
-   Internal system Company excluded from Super Administrator Company
    totals
-   Online status reuses the shared tracking freshness rule
-   Unassigned online Devices affect Device connectivity but do not
    count as online Vehicles
-   Unsynchronized Devices are excluded from the offline Device metric
-   Alert totals use existing tenant visibility
-   Unacknowledged Alerts are identified by `acknowledged_at = null`
-   Authentication and tenant-isolation feature coverage

The current Dashboard metrics form the initial fleet KPI set. Additional
time-based KPIs such as distance, duration, or speed should only be
added when a reporting period and aggregation contract are explicitly
defined.

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
-   Geofence CRUD, synchronization, associations, and permission
    reconciliation implemented
-   Secure Traccar event ingestion implemented for supported events
-   Persistent Alerts and acknowledgement implemented
-   Custom Alert Rule CRUD and runtime evaluation implemented
-   Reports implemented for trips, trip summary, stops, events, route,
    summary, hours, and combined report reads
-   Dashboard overview implemented with fleet, connectivity, Device, and
    Alert metrics
-   Targeted Dashboard and Reports suites passing at the latest
    checkpoint
-   Full test suite passing at the latest checkpoint
-   PHPStan / Larastan clean
-   Laravel Pint formatting/lint checks passing

Report export remains intentionally undefined and unimplemented.

# Next Development Point

The currently defined backend fleet-management, tracking, Geofence,
Alerts, Reports-read, and Dashboard overview functionality is
implemented.

The remaining explicit product decision is Reports export/report
generation through `reports.export`. Its output format and contract must
be defined before implementation.

Until that requirement exists, the appropriate next work is final
integration verification and documentation hardening rather than
inventing additional backend behavior.

Before any new feature work, review the latest source and confirm the
required domain contract, especially for:

1.  Report export format and content
2.  Any additional Dashboard time-based KPIs
3.  External Alert delivery channels
4.  Any future persistent FleetTrack Trip business entity
