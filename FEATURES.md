# FleetTrack Features

## Overview

This document tracks the functional capabilities currently implemented
in FleetTrack and the remaining product roadmap.

The latest completed backend checkpoint includes Live Tracking, vehicle
position history, aggregate trip summaries, and Traccar-detected vehicle
trip history.

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
-   Event-driven device synchronization
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

# Remaining Roadmap

## Geofences

Planned:

-   Geofence CRUD
-   Company ownership and isolation
-   Vehicle/device association as required
-   Entry/exit handling
-   Traccar integration
-   Notifications where required

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
-   Tracking feature tests passing
-   Full test suite passing
-   PHPStan / Larastan clean
-   Formatting/lint checks passing
-   Latest Trips functionality committed and pushed

------------------------------------------------------------------------

# Next Development Point

The current tracking foundation should be treated as completed
functionality.

After documentation cleanup, select the next module from the remaining
roadmap:

1.  Geofences
2.  Alerts
3.  Reports
4.  Dashboard

Before implementing the next module, review the latest project source
and confirm its requirements and architecture.
