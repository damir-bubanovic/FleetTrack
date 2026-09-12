# FleetTrack Features

## Overview

This document tracks the functional capabilities currently implemented
in FleetTrack and the remaining product roadmap.

The latest completed backend checkpoint includes the Geofence CRUD,
company isolation, Vehicle associations, Traccar geofence synchronization,
and Traccar device/geofence permission synchronization. Live Tracking,
position history, aggregate trip summaries, and Traccar-detected trip
history remain completed foundations.

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
-   Tenant isolation tests
-   Tracking authorization through `tracking.view`

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

## Devices

-   Full CRUD
-   Vehicle assignment
-   Company isolation
-   Validation
-   Authorization
-   Traccar synchronization state
-   Feature tests

------------------------------------------------------------------------

# Traccar Integration

## Core Integration

-   `TraccarClient`
-   `TraccarDeviceService`
-   `TraccarGeofenceService`
-   `PositionService`
-   `ReportService`
-   `DeviceData` DTO
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

Device synchronization writes follow:

``` text
Action
→ Event
→ Listener
→ Queue Job
→ Traccar service
→ Traccar REST API
```

## Tracking and Report Reads

Tracking data is read synchronously through the Traccar service layer.

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

# Technical Features

-   Laravel 13
-   PHP 8.3+
-   Laravel Sail development environment
-   Laravel Sanctum
-   Spatie Laravel Permission with Teams
-   Redis queues
-   Queue retry support
-   Event-driven device and geofence synchronization
-   Service layer for Traccar
-   DTO pattern
-   Action pattern
-   Form Requests
-   API Resources
-   Policies
-   Multi-tenant visibility scopes
-   PHPStan / Larastan
-   Laravel Pint
-   Pest tests
-   Laravel HTTP fakes for Traccar integration tests

------------------------------------------------------------------------

# Geofences

The Geofence module is partially implemented and is the current development
area.

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

-   Create geofence in Traccar
-   Update geofence in Traccar
-   Delete geofence from Traccar
-   Store Traccar geofence ID
-   Track geofence synchronization timestamp
-   Event/listener/queue-job synchronization flow
-   Retryable synchronization jobs
-   Dedicated Traccar geofence DTO/service coverage

Geofence synchronization writes follow the same application boundary as
device synchronization:

``` text
Action
→ Event
→ Listener
→ Queue Job
→ Traccar service
→ Traccar REST API
```

## Geofence Vehicle Associations

FleetTrack models Geofence associations against Vehicles. Traccar permission
synchronization resolves the Vehicle's assigned Device and uses its
`traccar_device_id`.

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

## Geofence Work Remaining

Still to implement before the Geofence module is considered complete:

-   Geofence entry/exit event handling
-   Determine and implement notification behavior required for geofence events
-   Complete final Geofence module documentation and integration verification

------------------------------------------------------------------------

# Remaining Roadmap

## Alerts

Planned alert areas include:

-   Overspeed
-   Ignition
-   Geofence events
-   Device offline
-   Custom alert rules
-   Alert history and acknowledgement as requirements are defined

## Reports

The reporting module remains to be designed beyond the tracking-specific
summaries and Traccar trip history already implemented.

Potential report areas:

-   Trips
-   Distance
-   Driver activity
-   Vehicle utilization
-   Export/report generation requirements

Do not duplicate existing tracking endpoints when designing the
reporting module.

## Dashboard

Planned:

-   Fleet overview
-   Active/online vehicles
-   Offline devices
-   Alerts summary
-   Fleet KPIs

------------------------------------------------------------------------

# Future Product Decisions

The following should be designed only when product requirements require
them:

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

Traccar supports stop reporting, but a dedicated FleetTrack stops
API/module has not yet been implemented.

Add it when required by the product roadmap.

------------------------------------------------------------------------

# Current Quality Status

At the latest completed checkpoint:

-   Authentication and authorization foundation implemented
-   Core company/fleet/driver/vehicle/device backend implemented
-   Traccar device synchronization implemented
-   Live Tracking implemented
-   Vehicle position history implemented
-   Aggregate vehicle trip summary implemented
-   Traccar-detected trip history implemented
-   Geofence CRUD and tenant authorization implemented
-   Traccar geofence lifecycle synchronization implemented
-   Geofence ↔ Vehicle associations implemented
-   Traccar device/geofence permission synchronization implemented
-   Association reconciliation for synchronization-order gaps implemented
-   Full test suite passing at the latest checkpoint
-   PHPStan / Larastan clean
-   Formatting/lint checks passing
-   Latest Geofence Vehicle association and Traccar sync checkpoint committed

------------------------------------------------------------------------

# Next Development Point

The tracking foundation is completed. Geofences are the active module and
are partially completed.

Continue the Geofence module with:

1.  Geofence entry/exit event handling
2.  Geofence-event notification behavior where required
3.  Final Geofence integration verification and documentation cleanup

After Geofences are complete, continue the remaining roadmap in this order:

1.  Alerts
2.  Reports
3.  Dashboard

Before implementing each remaining area, review the latest project source and
confirm the required domain behavior without duplicating capabilities already
provided by Traccar or the tracking endpoints.
