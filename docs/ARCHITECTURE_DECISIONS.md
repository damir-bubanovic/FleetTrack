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
-   `PositionService`
-   `ReportService`
-   `DeviceData`

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
sail composer lint
sail composer types:check
sail artisan test
```

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

Current tracking endpoints:

``` text
GET /api/v1/tracking/positions
GET /api/v1/tracking/vehicles/{vehicle}
GET /api/v1/tracking/vehicles/{vehicle}/positions
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

Remaining major roadmap areas:

1.  Geofences
2.  Alerts
3.  Reports
4.  Dashboard

The latest completed development checkpoint is the Traccar-backed
detected vehicle trip-history functionality. It has been tested,
statically analyzed, committed, and pushed.
