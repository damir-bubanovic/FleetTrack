# FleetTrack Architecture

## 1. Overview

FleetTrack is a multi-tenant fleet management and GPS tracking backend
built with Laravel and integrated with Traccar.

FleetTrack owns business-domain data such as companies, fleets, users,
drivers, vehicles, devices, permissions, and application workflows.

Traccar owns GPS-domain functionality such as device position data and
GPS trip detection.

The architecture separates these responsibilities so that FleetTrack
does not duplicate tracking algorithms already provided by Traccar.

------------------------------------------------------------------------

# 2. Technology Stack

Current backend baseline:

-   PHP `^8.3`
-   Laravel `^13.17`
-   Laravel Sanctum
-   Spatie Laravel Permission with Teams
-   MySQL
-   Redis queues
-   Laravel Sail
-   Pest
-   PHPStan / Larastan
-   Laravel Pint
-   Traccar REST API

------------------------------------------------------------------------

# 3. Application Layers

FleetTrack follows a layered Laravel architecture.

Typical application request flow:

``` text
HTTP Request
    ↓
Route
    ↓
Middleware
    ↓
Form Request
    ↓
Controller
    ↓
Authorization
    ↓
Action
    ↓
Model / Service
    ↓
API Resource
    ↓
JSON Response
```

Responsibilities are intentionally separated.

## Routes

Routes define the public API surface and map requests to controllers.

API routes are versioned under `/api/v1`.

## Middleware

Middleware handles cross-cutting concerns such as authentication and
permission-team context.

Protected routes use Sanctum authentication and the existing team-aware
permission setup.

## Form Requests

Form Requests own request validation.

Controllers should not contain validation rules.

## Controllers

Controllers remain thin.

Their responsibilities are primarily:

-   Accept validated requests
-   Resolve the authenticated user
-   Authorize operations
-   Invoke Actions
-   Return API Resources

Business logic should not be implemented directly in controllers.

## Actions

Actions contain application/business logic.

Examples currently used by the tracking subsystem include:

-   `GetLivePositions`
-   `GetVehicleLivePosition`
-   `GetVehiclePositionHistory`
-   `GetVehicleTripSummary`
-   `GetVehicleTrips`

CRUD modules also use Actions for create/update/delete behavior.

## Services

Services isolate external systems and infrastructure-specific behavior.

Traccar communication is centralized in the Traccar service layer.

## API Resources

Resources define stable application-facing JSON contracts.

Raw Traccar responses should not be treated as FleetTrack's public API
contract when transformation or unit normalization is required.

------------------------------------------------------------------------

# 4. Multi-Tenant Architecture

FleetTrack uses company-based multi-tenancy.

Core business entities are associated with a company either directly or
through their relationships.

Tenant rules:

-   Company users can access only data visible to their company.
-   Super Administrators can have global visibility.
-   Queries use visibility scopes such as `visibleTo()` where available.
-   Spatie Permission Teams provides team-aware roles and permissions.
-   `SetPermissionTeam` establishes the correct permission-team context.
-   Policies/permissions provide centralized authorization.

Tenant isolation must also be applied before external Traccar data is
exposed.

A Traccar device ID by itself is never sufficient authorization.
FleetTrack first resolves a locally visible Device/Vehicle and then
requests the corresponding Traccar data.

------------------------------------------------------------------------

# 5. Core Domain

Current backend domain areas include:

``` text
Company
 ├── Fleets
 ├── Users
 ├── Drivers
 ├── Vehicles
 │    ├── Device
 │    └── Geofences (many-to-many)
 ├── Geofences
 └── Permissions / Roles
```

The exact relationships and foreign keys are defined by the current
Eloquent models and migrations and should be treated as the
implementation source of truth.

## Company

Top-level tenant/business organization.

## Fleet

Groups vehicles within a company.

## User

Authenticated FleetTrack user with team-aware roles and permissions.

## Driver

Company-scoped driver domain entity.

## Vehicle

Fleet-managed vehicle belonging to the tenant domain.

## Device

Tracking device associated with a vehicle.

Devices may contain a `traccar_device_id`, linking the FleetTrack device
to the corresponding Traccar device.

## Geofence

Company-scoped geographic boundary owned by FleetTrack and synchronized
to Traccar.

Geofences may contain a `traccar_geofence_id` and `last_sync_at` value.
FleetTrack associates Geofences with Vehicles through the
`geofence_vehicle` pivot table. Traccar itself associates a geofence with
the Vehicle's synchronized Device, so the integration resolves:

``` text
Geofence -> traccar_geofence_id
Vehicle -> Device -> traccar_device_id
```

This keeps the public CRM/domain API vehicle-oriented while isolating
Traccar-specific device permission behavior in the integration layer.

------------------------------------------------------------------------

# 6. Authentication and Authorization

## Authentication

Laravel Sanctum provides API authentication.

Current authentication endpoints include:

``` text
POST /api/v1/auth/login
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

Protected API routes require authenticated users.

## Authorization

Authorization uses:

-   Laravel Policies
-   Spatie Laravel Permission
-   Teams
-   Company visibility scopes

Tracking endpoints authorize the `tracking.view` ability.

Authorization and tenant visibility are complementary:

``` text
Authentication
    ↓
Permission / Policy authorization
    ↓
Tenant-visible query
    ↓
Application operation
```

------------------------------------------------------------------------

# 7. Traccar Integration Architecture

All Traccar HTTP communication goes through the Traccar service layer.

Current core classes include:

``` text
TraccarClient
├── TraccarDeviceService
├── TraccarGeofenceService
├── PositionService
└── ReportService
```

`TraccarClient` owns common HTTP configuration such as:

-   Base URL
-   Basic authentication
-   JSON acceptance
-   Timeout
-   SSL verification

Higher-level services own endpoint-specific operations.

## Device Service

`TraccarDeviceService` manages device operations against Traccar.

Device data is represented through the integration DTO layer where
appropriate.

## Geofence Service

`TraccarGeofenceService` manages geofence lifecycle operations and
Traccar device/geofence permission links.

Current responsibilities include:

-   Create/update/delete Traccar geofences
-   Attach a geofence to a Traccar device through `POST /permissions`
-   Detach a geofence from a Traccar device through `DELETE /permissions`

## Position Service

`PositionService` reads Traccar positions.

It is currently used for:

-   Latest positions
-   Per-vehicle position
-   Historical positions
-   Position-based aggregate trip summary input

## Report Service

`ReportService` reads Traccar reports.

It currently exposes trip reports through:

``` text
GET /reports/trips
```

This powers FleetTrack's detected vehicle trip-history endpoint.

------------------------------------------------------------------------

# 8. Device Synchronization

FleetTrack owns device lifecycle changes, then synchronizes those
changes to Traccar asynchronously.

The architectural flow is:

``` text
API Request
    ↓
Controller
    ↓
Action
    ↓
FleetTrack database change
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

Current synchronization jobs include:

-   `SyncDeviceToTraccar`
-   `UpdateDeviceInTraccar`
-   `DeleteDeviceFromTraccar`

Benefits of this architecture:

-   API writes are not unnecessarily blocked by Traccar.
-   Synchronization can be retried.
-   External failures are isolated from the main request.
-   Traccar integration remains outside controllers and models.

------------------------------------------------------------------------

# 9. Geofence Architecture

FleetTrack owns Geofence CRUD, tenant ownership, Vehicle associations,
and synchronization state. Traccar owns the tracking-engine behavior of
evaluating device positions against geofence boundaries.

## Geofence Lifecycle Synchronization

Geofence create/update/delete writes follow the same asynchronous
integration boundary as Device writes:

``` text
API Request
    ↓
Controller
    ↓
Action
    ↓
FleetTrack database change
    ↓
GeofenceCreated / GeofenceUpdated / GeofenceDeleted
    ↓
Listener
    ↓
Queue Job
    ↓
TraccarGeofenceService
    ↓
Traccar REST API
```

Current jobs include:

-   `SyncGeofenceToTraccar`
-   `UpdateGeofenceInTraccar`
-   `DeleteGeofenceFromTraccar`

The local Geofence stores `traccar_geofence_id` and `last_sync_at` after
successful synchronization. Client requests cannot directly control
these Traccar-managed fields.

## Vehicle Associations

FleetTrack models Geofence membership against Vehicles rather than
exposing Traccar Device permissions as the business-domain API.

``` text
Geofence
    ↕ geofence_vehicle
Vehicle
    ↓
Device
    ↓
traccar_device_id
```

The current association endpoints are:

``` text
POST   /api/v1/geofences/{geofence}/vehicles/{vehicle}
DELETE /api/v1/geofences/{geofence}/vehicles/{vehicle}
```

Attach/detach Actions enforce that the Geofence and Vehicle belong to
the same company. Operations are idempotent and dispatch domain events
only when the pivot state actually changes.

Association synchronization flow:

``` text
VehicleAttachedToGeofence / VehicleDetachedFromGeofence
    ↓
Listener
    ↓
AttachGeofenceToDeviceInTraccar /
DetachGeofenceFromDeviceInTraccar
    ↓
TraccarGeofenceService
    ↓
Traccar /permissions
```

Queued association jobs re-read current FleetTrack state before making
external calls. This protects against stale jobs, including an attach
that was removed before execution and a detach followed by a reattach.

## Synchronization-Order Reconciliation

An association can exist before either side has received its Traccar ID.
Association jobs therefore safely return when the Geofence or Device is
not synchronized yet.

To prevent the association from being permanently missed:

-   `SyncGeofenceToTraccar` requeues association jobs for all currently
    attached Vehicles after the Geofence receives its Traccar ID.
-   `SyncDeviceToTraccar` requeues association jobs for all current
    Geofences of the Device's Vehicle after the Device receives its
    Traccar ID.

This provides eventual synchronization regardless of whether the
association, Geofence sync, or Device sync completes first.

## Geofence Event Handling

Geofence entry/exit event handling is not implemented yet. This is the
next Geofence architecture slice.

The implementation should consume Traccar's geofence event behavior
rather than implement a second position-in-polygon engine in FleetTrack.
Tenant-visible FleetTrack entities must be resolved before any external
event data is exposed or used by application workflows.

------------------------------------------------------------------------

# 10. Tracking Read Architecture

Tracking/report reads use synchronous service calls because the API
caller requires the current Traccar result in the response.

Typical flow:

``` text
API Request
    ↓
LiveTrackingController
    ↓
tracking.view authorization
    ↓
Tracking Action
    ↓
Resolve FleetTrack-visible Device
    ↓
PositionService / ReportService
    ↓
Traccar REST API
    ↓
Tracking API Resource
    ↓
JSON Response
```

This synchronous read flow is intentionally different from asynchronous
device synchronization writes.

------------------------------------------------------------------------

# 11. Current Tracking API

The current tracking API consists of five endpoints.

## Live Positions

``` text
GET /api/v1/tracking/positions
```

Action:

``` text
GetLivePositions
```

Responsibilities include:

-   Resolve visible synchronized devices
-   Read current Traccar positions
-   Filter results to authorized FleetTrack entities
-   Support fleet filtering
-   Support vehicle filtering
-   Return live tracking resources
-   Provide online/offline status

## Vehicle Live Position

``` text
GET /api/v1/tracking/vehicles/{vehicle}
```

Action:

``` text
GetVehicleLivePosition
```

Returns the latest position for a visible vehicle with a synchronized
device.

## Vehicle Position History

``` text
GET /api/v1/tracking/vehicles/{vehicle}/positions
```

Action:

``` text
GetVehiclePositionHistory
```

Validation:

``` text
VehiclePositionHistoryRequest
```

Current range rules:

-   `from` is required
-   `to` is required
-   `to` must be after `from`
-   Maximum range is 7 days

Historical positions are retrieved from Traccar through
`PositionService`.

## Vehicle Trip Summary

``` text
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
```

Action:

``` text
GetVehicleTripSummary
```

This is an aggregate calculation over an explicitly requested
position-history range.

Current output includes:

-   Position count
-   Start/end time
-   Duration
-   Distance
-   Average sampled speed
-   Maximum speed
-   Moving time
-   Stopped time

The API contract exposes explicit units including:

-   `distance_km`
-   `duration_seconds`
-   `speed_unit: knots`

This endpoint is not responsible for detecting individual trips.

## Vehicle Detected Trips

``` text
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

Action:

``` text
GetVehicleTrips
```

Integration:

``` text
GetVehicleTrips
    ↓
ReportService
    ↓
Traccar /reports/trips
```

FleetTrack resolves the visible synchronized device before sending the
Traccar report request.

`VehicleTripResource` transforms the Traccar report into the FleetTrack
API contract.

The current normalized contract includes:

-   Start/end times
-   Start/end coordinates
-   Distance in kilometers
-   Duration in seconds
-   Average speed
-   Maximum speed
-   Speed unit in knots
-   Addresses
-   Traccar device/driver references where available

------------------------------------------------------------------------

# 12. Trip Detection Ownership

GPS trip detection belongs to Traccar.

FleetTrack intentionally does not implement its own speed-sample trip
segmentation algorithm.

Architecture:

``` text
GPS data
    ↓
Traccar
    ↓
Traccar trip detection
    ↓
/reports/trips
    ↓
ReportService
    ↓
GetVehicleTrips
    ↓
VehicleTripResource
    ↓
FleetTrack API
```

This avoids two competing definitions of a trip.

`GetVehicleTripSummary` remains valid because it answers a different
question: it calculates aggregate statistics over a user-selected
historical range rather than deciding where individual trips begin and
end.

------------------------------------------------------------------------

# 13. Persistent Trip Domain Model

A FleetTrack `Trip` model is not currently required for detected GPS
trips.

Traccar remains the source of truth for detected trip data.

A persistent FleetTrack Trip entity should be introduced only if
FleetTrack needs to own business data attached to a trip, for example:

-   Notes
-   Approvals
-   Billing
-   Corrections
-   Audit history
-   Business classifications
-   Workflow state

If introduced later, it should build on Traccar-detected trips rather
than replace Traccar's GPS detection algorithm.

------------------------------------------------------------------------

# 14. API Resource Contracts and Units

External-service field names and units should be normalized before they
become FleetTrack API contracts.

Current tracking conventions include:

``` text
distance_km
duration_seconds
average_speed
max_speed
speed_unit
```

Traccar trip reports currently provide:

-   Distance in meters
-   Duration in seconds
-   Speed in knots

`VehicleTripResource` converts trip distance from meters to kilometers
and exposes duration explicitly as seconds.

The aggregate trip-summary endpoint also exposes distance in kilometers
and speed in knots.

------------------------------------------------------------------------

# 15. Error and Empty-State Behavior

Tracking Actions distinguish between FleetTrack visibility/device state
and Traccar response state.

Examples:

-   Unsynced devices do not trigger unnecessary Traccar requests.
-   Another company's vehicle cannot be used to expose external tracking
    data.
-   Empty Traccar trip/history reports produce appropriate empty
    application responses.
-   Missing current position can produce a not-found response for the
    per-vehicle live endpoint.
-   Invalid historical date ranges are rejected before the Traccar
    request.

External HTTP errors are propagated through the service/action flow
using Laravel HTTP response error handling.

------------------------------------------------------------------------

# 16. Testing Architecture

Tests use Pest.

Traccar is isolated in automated tests with Laravel HTTP fakes.

Important test categories include:

-   Authentication
-   CRUD behavior
-   Authorization
-   Company isolation
-   Tracking visibility
-   Filter behavior
-   Online/offline state
-   Historical range validation
-   Traccar request parameters
-   Trip-summary calculations
-   Traccar-detected trip transformation
-   Unsynced-device behavior
-   Empty Traccar responses
-   Prevention of unauthorized external calls

Queue-related integration behavior should use queue fakes where
appropriate.

No feature test should depend on a live Traccar server.

------------------------------------------------------------------------

# 17. Code Quality

The project uses:

-   Laravel Pint for formatting
-   PHPStan / Larastan for static analysis
-   Pest for automated tests

Expected pre-commit checks for a meaningful functionality slice:

``` bash
sail composer lint:check
sail composer types:check
sail artisan test
```

When Pint reports fixable formatting issues, run `sail composer lint`,
then rerun all three pre-commit checks.

New work should preserve zero PHPStan errors and a passing test suite.

------------------------------------------------------------------------

# 18. Remaining Architecture Roadmap

The current development sequence is:

``` text
Geofence entry/exit handling
    ↓
Alerts
    ↓
Reports
    ↓
Dashboard
```

## Geofences

Geofence CRUD, tenant authorization, Traccar lifecycle synchronization,
Vehicle associations, Traccar permission synchronization, stale-job
protection, and synchronization-order reconciliation are implemented.

Remaining Geofence work:

-   Geofence entry/exit event handling
-   Define and implement notification behavior required for geofence
    events
-   Final Geofence integration verification

## Alerts

Architecture still needs to define application-owned alert rules, event
ingestion, history, acknowledgement, and notification behavior.
Geofence events should feed this area where product requirements require
alerts or notifications.

## Reports

Future reporting should reuse existing Traccar capabilities and current
tracking Actions where appropriate rather than duplicating them.

## Dashboard

Dashboard queries should aggregate existing FleetTrack and tracking
capabilities without moving business logic into controllers.

------------------------------------------------------------------------

# 19. Current Architecture Checkpoint

At the latest completed checkpoint:

``` text
Authentication                         COMPLETE
Authorization / Multi-tenancy          COMPLETE
Companies                              COMPLETE
Fleets                                 COMPLETE
Drivers                                COMPLETE
Vehicles                               COMPLETE
Devices                                COMPLETE
Traccar device synchronization         COMPLETE
Live Tracking foundation               COMPLETE
Vehicle position history               COMPLETE
Aggregate trip summary                 COMPLETE
Traccar-detected trip history          COMPLETE
Geofence CRUD / authorization          COMPLETE
Traccar geofence lifecycle sync        COMPLETE
Geofence <-> Vehicle associations      COMPLETE
Traccar geofence/device permissions    COMPLETE
Association reconciliation             COMPLETE
Geofence entry/exit handling           NEXT
Alerts                                 PLANNED
Reports                                PLANNED
Dashboard                              PLANNED
```

The latest Geofence Vehicle association and Traccar synchronization
checkpoint has passed Pint formatting/lint checks, PHPStan/Larastan, and
the full Pest test suite, and has been committed.

When beginning a fresh development session, review the latest source
first and continue with Geofence entry/exit event handling rather than
recreating completed Geofence, tracking, or synchronization work.
