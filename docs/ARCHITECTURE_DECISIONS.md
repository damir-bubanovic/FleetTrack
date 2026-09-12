# Architecture Decisions

## Purpose

This document records durable architectural decisions made during
FleetTrack development and the reasoning behind them.

It should describe decisions that future contributors need to preserve
unless requirements or architecture deliberately change.

------------------------------------------------------------------------

# ADR-001: Laravel as the Backend Framework

**Status:** Accepted

**Decision**

Use Laravel as the primary backend framework.

Current project baseline:

-   PHP `^8.3`
-   Laravel `^13.17`

**Rationale**

-   Mature ecosystem
-   Strong testing support
-   Queue system
-   Policies and authorization
-   First-party authentication support
-   HTTP client and integration tooling
-   Strong community and package ecosystem

------------------------------------------------------------------------

# ADR-002: Company-Based Multi-Tenancy

**Status:** Accepted

**Decision**

Use company-based tenancy in a shared application/database architecture
rather than separate databases per tenant.

**Rationale**

-   Simpler deployment
-   Easier cross-tenant administration and reporting where authorized
-   Lower operational complexity
-   Fits the current FleetTrack domain model

**Implementation**

-   `company_id` on applicable business entities
-   Policy-based authorization
-   Visibility scopes such as `visibleTo()`
-   Spatie Laravel Permission with Teams
-   `SetPermissionTeam` middleware for protected API requests

External Traccar data must also respect FleetTrack tenant visibility. A
Traccar identifier alone never grants access.

------------------------------------------------------------------------

# ADR-003: Thin Controllers

**Status:** Accepted

**Decision**

Controllers orchestrate HTTP requests and responses only.

Business/application rules belong in Action classes.

**Controller responsibilities**

-   Receive validated requests
-   Resolve the authenticated user
-   Perform authorization
-   Invoke Actions
-   Return API Resources

**Benefits**

-   Easier testing
-   Better separation of concerns
-   Reusable application logic
-   Smaller controllers

------------------------------------------------------------------------

# ADR-004: Actions for Application and Business Logic

**Status:** Accepted

**Decision**

Use Action classes for meaningful application/business operations.

Examples include:

-   `CreateCompany`
-   `UpdateFleet`
-   `CreateVehicle`
-   `CreateDevice`
-   `UpdateDevice`
-   `DeleteDevice`
-   `GetLivePositions`
-   `GetVehicleLivePosition`
-   `GetVehiclePositionHistory`
-   `GetVehicleTripSummary`
-   `GetVehicleTrips`
-   `CreateGeofence`
-   `UpdateGeofence`
-   `DeleteGeofence`
-   `AttachVehicleToGeofence`
-   `DetachVehicleFromGeofence`

Actions may persist FleetTrack data, coordinate domain behavior,
dispatch events, or call dedicated services depending on the operation.

------------------------------------------------------------------------

# ADR-005: Event-Driven Traccar Device Synchronization

**Status:** Accepted

**Decision**

Synchronize FleetTrack device lifecycle writes to Traccar
asynchronously.

**Flow**

``` text
FleetTrack API
→ Controller
→ Action
→ FleetTrack persistence
→ Domain Event
→ Listener
→ Queue Job
→ TraccarDeviceService
→ Traccar REST API
```

**Current jobs**

-   `SyncDeviceToTraccar`
-   `UpdateDeviceInTraccar`
-   `DeleteDeviceFromTraccar`

**Rationale**

-   Faster API write responses
-   Retry support
-   Loose coupling
-   Better fault isolation
-   External Traccar availability does not need to block normal
    FleetTrack persistence

This ADR applies specifically to synchronization writes. Tracking/report
reads follow ADR-011.

------------------------------------------------------------------------

# ADR-006: Dedicated Traccar Service Layer

**Status:** Accepted

**Decision**

All Traccar HTTP communication is isolated behind the Traccar
integration layer.

Current core integration classes include:

-   `TraccarClient`
-   `TraccarDeviceService`
-   `TraccarGeofenceService`
-   `PositionService`
-   `ReportService`
-   `DeviceData`
-   `GeofenceData`

**Rationale**

-   Controllers remain independent of HTTP implementation details
-   Actions operate against endpoint-specific services
-   Authentication, base URL, timeout, SSL, and common HTTP behavior
    stay centralized
-   Traccar integration is easier to test with HTTP fakes
-   New Traccar endpoints can be added without leaking HTTP concerns
    through the application

Controllers must not communicate with Traccar directly.

------------------------------------------------------------------------

# ADR-007: Queue Jobs Have a Single Responsibility

**Status:** Accepted

**Decision**

Each queue job performs one integration responsibility.

Current examples:

-   `SyncDeviceToTraccar`
-   `UpdateDeviceInTraccar`
-   `DeleteDeviceFromTraccar`
-   `SyncGeofenceToTraccar`
-   `UpdateGeofenceInTraccar`
-   `DeleteGeofenceFromTraccar`
-   `AttachGeofenceToDeviceInTraccar`
-   `DetachGeofenceFromDeviceInTraccar`

Jobs should:

-   Be retryable
-   Log/report failures
-   Avoid unrelated business responsibilities
-   Avoid unnecessary model dependencies when records may have been
    deleted before execution

------------------------------------------------------------------------

# ADR-008: Policy-Based and Permission-Based Authorization

**Status:** Accepted

**Decision**

Centralize authorization using Laravel Policies and the existing
permission system rather than scattering role/company conditionals
through controllers.

Tracking endpoints currently authorize the `tracking.view` ability.

Authorization works together with tenant-visible database queries;
neither mechanism replaces the other.

**Rationale**

-   Centralized authorization rules
-   Easier testing
-   Consistent behavior across endpoints
-   Reduced risk of tenant-data leakage

------------------------------------------------------------------------

# ADR-009: Feature-Test Driven API Development

**Status:** Accepted

**Decision**

New API functionality should include feature tests covering relevant
application boundaries.

Typical coverage includes:

-   Success paths
-   Validation
-   Authorization
-   Tenant isolation
-   Empty external-service responses
-   Unsynced entities
-   External request parameters
-   Queue dispatching with `Queue::fake()` where appropriate

Traccar calls are isolated with `Http::fake()`.

Automated tests must not depend on a live Traccar server.

------------------------------------------------------------------------

# ADR-010: Static Analysis and Code Quality Gates

**Status:** Accepted

**Decision**

Completed functionality must preserve the project's automated quality
gates.

Current expectations:

-   PHPStan / Larastan with zero errors
-   Laravel Pint compliant formatting
-   Passing targeted tests
-   Passing full test suite before committing a completed functionality
    slice

Standard final checks:

``` bash
sail composer lint:check
sail composer types:check
sail artisan test
```

If Pint reports fixable formatting issues, run:

``` bash
sail composer lint
```

Then rerun all three final checks. Do not use `sail artisan lint`.

------------------------------------------------------------------------

# ADR-011: Traccar Tracking and Report Reads Are Synchronous

**Status:** Accepted

**Decision**

Read-only tracking and report requests that are required to build the
current API response are performed synchronously through dedicated
Traccar services.

**Flow**

``` text
FleetTrack API Request
→ Controller
→ Authorization
→ Tracking Action
→ Resolve FleetTrack-visible Device
→ PositionService / ReportService
→ Traccar REST API
→ API Resource
→ FleetTrack API Response
```

Current examples:

-   Live positions
-   Per-vehicle live position
-   Vehicle position history
-   Traccar trip reports

**Rationale**

The caller requires the current Traccar result in the same request.
Queueing these reads would add complexity without providing the benefits
required for the current use cases.

This does not conflict with ADR-005. Device synchronization writes
remain asynchronous.

------------------------------------------------------------------------

# ADR-012: Traccar Owns GPS Trip Detection

**Status:** Accepted

**Decision**

Use Traccar as the source of truth for GPS trip detection.

FleetTrack must not maintain a competing position/speed-based trip
segmentation algorithm for normal detected-trip functionality.

Detected vehicle trips are currently retrieved from Traccar through:

``` text
GET /reports/trips
```

FleetTrack integration flow:

``` text
GET /api/v1/tracking/vehicles/{vehicle}/trips
→ LiveTrackingController
→ GetVehicleTrips
→ ReportService
→ Traccar /reports/trips
→ VehicleTripResource
```

**Rationale**

-   Traccar already provides dedicated trip detection.
-   Traccar owns the underlying GPS tracking domain.
-   Reimplementing detection in FleetTrack would create two definitions
    of a trip.
-   Detection rules could diverge over time.
-   FleetTrack should focus on authorization, business context, and
    application-facing response contracts.

**Important distinction**

`GetVehicleTripSummary` remains part of FleetTrack.

It calculates aggregate metrics over a user-selected historical position
range. It does not determine individual trip boundaries and therefore
does not compete with Traccar trip detection.

------------------------------------------------------------------------

# ADR-013: Do Not Persist Detected Trips Until FleetTrack Owns Trip Business Data

**Status:** Accepted

**Decision**

Do not introduce a FleetTrack `Trip` persistence model solely to
duplicate Traccar-detected trip reports.

A persistent FleetTrack Trip entity should be introduced only when the
application needs to own additional trip-specific business data or
workflows.

Examples that could justify persistence:

-   Notes
-   Approval state
-   Billing
-   Corrections
-   Audit history
-   Business classifications
-   Workflow state

If a persistent Trip domain is introduced later, it should build on or
reference Traccar-detected GPS trips rather than replace Traccar's
detection algorithm.

**Rationale**

-   Avoid duplicated source-of-truth data
-   Avoid premature persistence
-   Keep GPS detection responsibility in Traccar
-   Introduce domain complexity only when business requirements justify
    it

------------------------------------------------------------------------

# ADR-014: Normalize External Tracking Units in FleetTrack API Resources

**Status:** Accepted

**Decision**

Do not expose ambiguous Traccar measurement fields directly when
FleetTrack can provide an explicit application-facing contract.

Current conventions include:

-   `distance_km`
-   `duration_seconds`
-   `average_speed`
-   `max_speed`
-   `speed_unit`

For current Traccar trip reports:

-   Traccar distance is treated as meters and converted to kilometers.
-   Traccar trip duration is treated as seconds.
-   Traccar speed is exposed with `speed_unit: knots`.

**Rationale**

-   API consumers should not need Traccar-specific unit knowledge.
-   Explicit names reduce integration mistakes.
-   FleetTrack can keep its public contract stable even if external
    payload naming differs.

------------------------------------------------------------------------

# ADR-015: Historical Tracking Requests Use a Seven-Day Maximum Range

**Status:** Accepted

**Decision**

Vehicle historical tracking requests currently allow a maximum date
range of seven days.

The shared historical request validation requires:

-   `from`
-   `to`
-   `to` after `from`
-   Maximum interval of 168 hours

Exactly seven days is valid. A range greater than seven days is
rejected.

This validation currently applies to historical tracking functionality
that uses `VehiclePositionHistoryRequest`, including position history,
trip summary, and detected trip history.

**Rationale**

-   Bounds synchronous Traccar report/history requests
-   Prevents unexpectedly large API payloads
-   Provides a consistent current tracking API constraint

The limit can be revisited later if reporting requirements require
larger ranges or asynchronous report generation.

------------------------------------------------------------------------

------------------------------------------------------------------------

# ADR-016: FleetTrack Models Geofence Associations to Vehicles

**Status:** Accepted

**Decision**

Model the FleetTrack business-domain relationship as:

``` text
Geofence ↔ Vehicle
```

Do not expose a Geofence ↔ Device relationship as the primary FleetTrack
CRM model solely because Traccar permissions operate on devices.

The local relationship is persisted in the `geofence_vehicle` pivot
table.

When synchronizing the association to Traccar, resolve:

``` text
Geofence
→ traccar_geofence_id

Vehicle
→ Device
→ traccar_device_id
```

**Rationale**

-   Vehicles are the FleetTrack business entity users manage.
-   Devices are implementation/integration details of GPS tracking.
-   Keeping the public domain relationship at Vehicle level prevents
    Traccar-specific concepts from leaking into the CRM model.
-   The mapping can still be translated to Traccar's required
    device/geofence permission relationship at the integration boundary.

Cross-company Geofence ↔ Vehicle associations are forbidden.

------------------------------------------------------------------------

# ADR-017: Geofence Lifecycle Synchronization Is Event-Driven

**Status:** Accepted

**Decision**

Synchronize Geofence create/update/delete writes to Traccar
asynchronously using the same event-driven boundary used for Device
writes.

Typical flow:

``` text
Geofence Action
→ FleetTrack persistence
→ Domain Event
→ Listener
→ Queue Job
→ TraccarGeofenceService
→ Traccar REST API
```

Current lifecycle components include:

-   `GeofenceCreated`
-   `GeofenceUpdated`
-   `GeofenceDeleted`
-   `SyncGeofenceToTraccar`
-   `UpdateGeofenceInTraccar`
-   `DeleteGeofenceFromTraccar`

**Rationale**

-   Keeps FleetTrack persistence independent of temporary Traccar
    availability.
-   Provides retries and fault isolation.
-   Preserves the same integration architecture already established for
    Devices.
-   Keeps controllers and Actions free of direct HTTP concerns.

------------------------------------------------------------------------

# ADR-018: Geofence-to-Device Permissions Are Synchronized from Local Association Events

**Status:** Accepted

**Decision**

When the local Geofence ↔ Vehicle relationship changes, synchronize the
corresponding Traccar Geofence ↔ Device permission asynchronously.

Attach flow:

``` text
AttachVehicleToGeofence
→ VehicleAttachedToGeofence
→ SyncVehicleAttachedToGeofenceToTraccar
→ AttachGeofenceToDeviceInTraccar
→ TraccarGeofenceService
→ POST /permissions
```

Detach flow:

``` text
DetachVehicleFromGeofence
→ VehicleDetachedFromGeofence
→ SyncVehicleDetachedFromGeofenceToTraccar
→ DetachGeofenceFromDeviceInTraccar
→ TraccarGeofenceService
→ DELETE /permissions
```

The Traccar permission payload contains:

``` text
geofenceId
deviceId
```

Local attach/detach operations are idempotent. Association events are
dispatched only when the pivot state actually changes.

**Rationale**

-   FleetTrack remains the source of truth for the business association.
-   Traccar receives only the translated integration relationship.
-   Queue retries isolate external failures from the API request.
-   Idempotent local mutations prevent duplicate association events.

------------------------------------------------------------------------

# ADR-019: Geofence Permission Jobs Re-Check Current Local State

**Status:** Accepted

**Decision**

Queued Geofence permission jobs must validate the current FleetTrack
state before making a Traccar permission request.

An attach job must not call Traccar if the Geofence or Vehicle no longer
exists, the local association no longer exists, the Geofence is not yet
synchronized, the Vehicle has no Device, or the Device is not yet
synchronized.

A detach job must not call Traccar if the local association exists again.

**Rationale**

Queue execution is asynchronous. The state that caused a job to be
queued may no longer be valid when that job executes.

In particular, the detach guard protects this race:

``` text
detach locally
→ detach job queued
→ vehicle reattached locally
→ stale detach job executes
```

Without a current-state check, the stale job could incorrectly remove a
permission that FleetTrack now expects to exist.

------------------------------------------------------------------------

# ADR-020: Geofence Permission Synchronization Uses Reconciliation for Eventual Consistency

**Status:** Accepted

**Decision**

Do not require the Geofence, Device, and local association to become
synchronized in a particular order.

Association jobs may safely return when a required Traccar ID is not yet
available. Later lifecycle synchronization performs reconciliation.

Two reconciliation paths are required:

**Geofence-side reconciliation**

After `SyncGeofenceToTraccar` stores a new `traccar_geofence_id`, dispatch
`AttachGeofenceToDeviceInTraccar` for every Vehicle currently associated
with that Geofence.

**Device-side reconciliation**

After `SyncDeviceToTraccar` stores a new `traccar_device_id`, dispatch
`AttachGeofenceToDeviceInTraccar` for every Geofence currently associated
with that Device's Vehicle.

**Rationale**

This provides eventual consistency for both ordering cases:

``` text
association exists before Geofence synchronization
```

and:

``` text
association exists before Device synchronization
```

Neither reconciliation path replaces the other.

------------------------------------------------------------------------

# Current Architecture Baseline

Implemented:

-   Authentication
-   Authorization and company-based multi-tenancy
-   Companies
-   Fleets
-   Users
-   Drivers
-   Vehicles
-   Devices
-   Traccar device synchronization
-   Live Tracking
-   Per-vehicle live position
-   Live-position fleet/vehicle filtering
-   Online/offline tracking status
-   Vehicle position history
-   Aggregate vehicle trip summary
-   Traccar-detected vehicle trip history
-   Geofence CRUD and tenant authorization
-   Traccar Geofence lifecycle synchronization
-   Geofence ↔ Vehicle associations
-   Traccar Geofence ↔ Device permission synchronization
-   Geofence/Device association reconciliation

Current tracking endpoints:

``` text
GET /api/v1/tracking/positions
GET /api/v1/tracking/vehicles/{vehicle}
GET /api/v1/tracking/vehicles/{vehicle}/positions
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

Current Geofence endpoints:

``` text
GET /api/v1/geofences
POST /api/v1/geofences
GET /api/v1/geofences/{geofence}
PUT/PATCH /api/v1/geofences/{geofence}
DELETE /api/v1/geofences/{geofence}
POST /api/v1/geofences/{geofence}/vehicles/{vehicle}
DELETE /api/v1/geofences/{geofence}/vehicles/{vehicle}
```

The Geofence module is currently in progress.

The latest completed Geofence checkpoint is Geofence ↔ Vehicle
association synchronization, including Traccar permission attach/detach,
stale-job protection, and Geofence-side/Device-side reconciliation.

The next development slice is:

**Geofence entry/exit event handling.**

After the Geofence module is complete, the remaining major roadmap areas
are:

1.  Alerts
2.  Reports
3.  Dashboard
