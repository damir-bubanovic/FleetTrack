# Architecture Decisions

## Purpose

This document records durable architectural decisions made during
FleetTrack development and the reasoning behind them.

It should describe decisions that future contributors need to preserve
unless requirements or architecture deliberately change.

The source code remains authoritative if an older decision description
becomes stale.

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

Controllers must not communicate with Traccar directly.

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
-   Alert creation Actions
-   `ResolveAlertRule`
-   Alert Rule CRUD Actions

Actions may persist FleetTrack data, coordinate domain behavior,
dispatch events, or call dedicated services depending on the operation.

New HTTP surfaces should reuse existing Actions when the application
behavior is already represented there. The Reports vehicle-trip
endpoint, for example, reuses `GetVehicleTrips`.

------------------------------------------------------------------------

# ADR-005: Event-Driven Traccar Device Synchronization

**Status:** Accepted

**Decision**

Synchronize FleetTrack Device lifecycle writes to Traccar
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
-   Event-specific Traccar DTOs

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

Current examples include Device synchronization, Geofence
synchronization, and Geofence/Device permission synchronization jobs.

Jobs should:

-   Be retryable
-   Log/report failures
-   Avoid unrelated business responsibilities
-   Avoid unnecessary model dependencies when records may have been
    deleted before execution
-   Re-check current local desired state where stale queued work could
    otherwise overwrite newer state

------------------------------------------------------------------------

# ADR-008: Policy-Based and Permission-Based Authorization

**Status:** Accepted

**Decision**

Centralize authorization using Laravel Policies and the existing
permission system rather than scattering role/company conditionals
through controllers.

Model-specific CRUD uses Policies where ownership checks are required.

Capability-oriented endpoints may authorize permission abilities
directly.

Current examples include:

``` text
tracking.view
reports.view
reports.export
alerts.view
alerts.acknowledge
alert-rules.view
alert-rules.create
alert-rules.update
alert-rules.delete
```

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
-   Authentication
-   Authorization
-   Tenant isolation
-   Empty external-service responses
-   Unsynced entities
-   External request parameters
-   Queue dispatching with fakes where appropriate

Traccar calls are isolated with Laravel HTTP fakes.

Automated tests must not depend on a live Traccar server.

------------------------------------------------------------------------

# ADR-010: Static Analysis and Code Quality Gates

**Status:** Accepted

**Decision**

Completed functionality must preserve the project's automated quality
gates.

The standard commit-boundary quality gate is:

``` bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

**Expectations**

-   Laravel Pint formatting applied
-   Laravel Pint verification passes
-   PHPStan / Larastan reports zero errors
-   Targeted tests pass
-   Full test suite passes before committing a completed functionality
    slice

Do not use:

``` text
sail artisan lint
```

The project uses Composer scripts for linting.

------------------------------------------------------------------------

# ADR-011: Traccar Tracking and Report Reads Are Synchronous

**Status:** Accepted

**Decision**

Read-only tracking and report requests required to build the current API
response are performed synchronously through dedicated Traccar services.

**Flow**

``` text
FleetTrack API Request
→ Controller
→ Authorization
→ Action
→ Resolve FleetTrack-visible Device
→ PositionService / ReportService
→ Traccar REST API
→ API Resource
→ FleetTrack API Response
```

Current examples:

-   Live positions
-   Per-Vehicle live position
-   Vehicle position history
-   Traccar trip history
-   Reports vehicle trip report

**Rationale**

The caller requires the current Traccar result in the same request.
Queueing these reads would add complexity without providing the benefits
required for the current use cases.

This does not conflict with ADR-005 or ADR-017. External lifecycle
synchronization writes remain asynchronous.

------------------------------------------------------------------------

# ADR-012: Traccar Owns GPS Trip Detection

**Status:** Accepted

**Decision**

Use Traccar as the source of truth for GPS trip detection.

FleetTrack must not maintain a competing position/speed-based trip
segmentation algorithm for normal detected-trip functionality.

Detected Vehicle trips are retrieved from Traccar through:

``` text
GET /reports/trips
```

Current FleetTrack consumers include:

``` text
GET /api/v1/tracking/vehicles/{vehicle}/trips
GET /api/v1/reports/vehicles/{vehicle}/trips
```

Both reuse:

``` text
GetVehicleTrips
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
-   FleetTrack should focus on authorization, business context,
    reporting surfaces, and application-facing response contracts.

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

Vehicle historical tracking requests using the shared historical
tracking request currently allow a maximum date range of seven days.

The shared validation requires:

-   `from`
-   `to`
-   `to` after `from`
-   Maximum interval of 168 hours

Exactly seven days is valid. A range greater than seven days is
rejected.

This applies to tracking functionality using the historical tracking
request, including position history, aggregate trip summary, and
tracking trip history.

The dedicated Reports vehicle-trip request currently has its own
contract: `from` and `to` are required and `to` must be after `from`.

**Rationale**

-   Bounds synchronous tracking history requests
-   Prevents unexpectedly large tracking payloads
-   Allows Reports to evolve its own range/export constraints as
    reporting requirements are defined

------------------------------------------------------------------------

# ADR-016: FleetTrack Models Geofence Associations to Vehicles

**Status:** Accepted

**Decision**

Model the FleetTrack business-domain relationship as:

``` text
Geofence ↔ Vehicle
```

Do not expose a Geofence ↔ Device relationship as the primary FleetTrack
CRM model solely because Traccar permissions operate on Devices.

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
    Device/Geofence permission relationship at the integration boundary.

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

Current lifecycle components include Geofence created/updated/deleted
events and dedicated synchronization jobs.

**Rationale**

-   Keeps FleetTrack persistence independent of temporary Traccar
    availability.
-   Provides retries and fault isolation.
-   Preserves the integration architecture already established for
    Devices.
-   Keeps controllers and Actions free of direct HTTP concerns.

------------------------------------------------------------------------

# ADR-018: Geofence-to-Device Permissions Are Synchronized from Local Association Events

**Status:** Accepted

**Decision**

When the local Geofence ↔ Vehicle relationship changes, synchronize the
corresponding Traccar Geofence ↔ Device permission asynchronously.

Conceptual attach flow:

``` text
AttachVehicleToGeofence
→ local pivot change
→ VehicleAttachedToGeofence
→ Listener
→ permission synchronization job
→ TraccarGeofenceService
→ POST /permissions
```

Conceptual detach flow:

``` text
DetachVehicleFromGeofence
→ local pivot change
→ VehicleDetachedFromGeofence
→ Listener
→ permission removal job
→ TraccarGeofenceService
→ DELETE /permissions
```

The Traccar permission payload contains the resolved Traccar Geofence
and Device identifiers.

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

Queued Geofence permission jobs must validate current FleetTrack state
before making a Traccar permission request.

An attach job must not write stale state if, for example:

-   the Geofence or Vehicle no longer exists
-   the local association no longer exists
-   a required Traccar identifier is unavailable
-   the Vehicle no longer has the required synchronized Device

A detach job must not remove the Traccar permission if the local
association has been recreated.

**Rationale**

Queue execution is asynchronous. The state that caused a job to be
queued may no longer be valid when that job executes.

In particular:

``` text
detach locally
→ detach job queued
→ Vehicle reattached locally
→ stale detach job executes
```

must not result in removal of a permission FleetTrack now expects to
exist.

------------------------------------------------------------------------

# ADR-020: Geofence Permission Synchronization Uses Reconciliation for Eventual Consistency

**Status:** Accepted

**Decision**

Do not require the Geofence, Device, and local association to become
synchronized in a particular order.

Association synchronization may safely defer when a required Traccar ID
is not yet available. Later lifecycle synchronization performs
reconciliation.

Required reconciliation paths:

**Geofence-side reconciliation**

After initial Geofence synchronization stores its Traccar ID,
synchronize the currently associated Vehicles.

**Device-side reconciliation**

After initial Device synchronization stores its Traccar ID, synchronize
the Geofences currently associated with that Device's Vehicle.

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

# ADR-021: Traccar Webhook Events Use a Dedicated Authentication Boundary

**Status:** Accepted

**Decision**

Receive supported Traccar events through:

``` text
POST /api/v1/traccar/events
```

and protect that endpoint with `VerifyTraccarWebhook` instead of
Sanctum.

Traccar is a service caller rather than an authenticated FleetTrack
user.

**Current supported event types**

``` text
geofenceEnter
geofenceExit
deviceOverspeed
ignitionOn
ignitionOff
deviceOffline
```

**Rationale**

-   Keeps machine-to-machine authentication separate from user
    authentication.
-   Prevents the external webhook from requiring a FleetTrack user
    token.
-   Gives the Traccar integration a dedicated verification boundary.
-   Allows normal authenticated API routes to remain protected by
    Sanctum and permission-team middleware.

------------------------------------------------------------------------

# ADR-022: Translate Traccar Events Before Triggering Application Behavior

**Status:** Accepted

**Decision**

Do not pass raw Traccar webhook payloads directly into Alert
persistence.

Supported Traccar events are validated and translated through
event-specific DTOs/handlers before FleetTrack application events are
dispatched.

Conceptual flow:

``` text
Traccar payload
→ event-specific validation
→ DTO
→ Handler
→ FleetTrack application event
→ queued listener
→ Alert Action
```

**Rationale**

-   Isolates Traccar payload shape from application logic.
-   Produces typed, testable boundaries.
-   Keeps Alert Actions independent of webhook/controller details.
-   Makes each supported event type explicit.

------------------------------------------------------------------------

# ADR-023: Alerts Are Persistent FleetTrack Business Records

**Status:** Accepted

**Decision**

Persist supported tracking events as FleetTrack Alert records when they
enter the Alert pipeline.

Current supported Alert types:

``` text
geofence_enter
geofence_exit
overspeed
ignition_on
ignition_off
device_offline
```

The Alerts API provides:

``` text
GET   /api/v1/alerts
GET   /api/v1/alerts/{alert}
PATCH /api/v1/alerts/{alert}/acknowledge
```

Alerts are company-scoped and acknowledgement is idempotent.

Where an external Traccar event identifier is available, Alert creation
uses it as part of the idempotency strategy.

**Rationale**

-   Alerts are application/business state rather than transient HTTP
    responses.
-   Persistence supports history and acknowledgement.
-   Tenant-scoped records can be authorized independently of Traccar.
-   Idempotency protects against repeated external event delivery.

------------------------------------------------------------------------

# ADR-024: Custom Alert Rules Augment Default Alert Generation

**Status:** Accepted

**Decision**

Support configurable Alert Rules while preserving default Alert
generation when no custom rule matches.

An Alert Rule belongs to a Company and may optionally target a Vehicle.

``` text
vehicle_id = null
    → company-wide rule

vehicle_id = value
    → Vehicle-specific rule
```

Current supported rule types:

``` text
overspeed
geofence_enter
geofence_exit
ignition_on
ignition_off
device_offline
```

Current severity values:

``` text
info
warning
critical
```

**Rationale**

Custom configuration should allow a Company to change relevant Alert
behavior without making the existence of a rule mandatory for normal
Alert generation.

This preserves the established alert pipeline for Companies that have
not configured custom rules.

------------------------------------------------------------------------

# ADR-025: Alert Rule Resolution Prefers Vehicle-Specific Rules with Company-Wide Fallback

**Status:** Accepted

**Decision**

Centralize runtime Alert Rule matching in `ResolveAlertRule`.

Resolution rules:

1.  Match the Vehicle's Company.
2.  Match the Alert type.
3.  Consider active rules only.
4.  Consider Vehicle-specific and company-wide candidates.
5.  Evaluate Vehicle-specific candidates before company-wide candidates.
6.  Return the first candidate whose conditions match.
7.  Return `null` when no custom rule matches.

A company-wide rule may therefore match when a Vehicle-specific
candidate does not satisfy its condition.

For overspeed rules:

``` text
conditions.speed_limit_kmh
```

is compared with the actual event speed converted to km/h.

The match is strict:

``` text
actual_speed_kmh > speed_limit_kmh
```

Equality does not match.

Currently supported non-overspeed rule types require no additional
runtime condition.

**Rationale**

-   Vehicle-specific configuration should take precedence when
    applicable.
-   A non-matching specific threshold should not suppress an otherwise
    applicable Company rule.
-   Centralized resolution keeps individual Alert Actions simple and
    consistent.
-   Returning `null` preserves default Alert behavior.

------------------------------------------------------------------------

# ADR-026: Alert Rules Override Severity, Not the Entire Alert Pipeline

**Status:** Accepted

**Decision**

For the currently implemented rule system, a matching Alert Rule
overrides the Alert severity.

If no custom rule matches, each Alert source retains its default
severity.

Current defaults include:

``` text
geofence entry/exit    info
ignition on/off        info
overspeed              warning
device offline         warning
```

The Alert Rule `name` is configuration metadata and is not currently
used as the generated Alert message.

**Rationale**

-   Keeps custom-rule behavior intentionally narrow.
-   Preserves established Alert messages and event semantics.
-   Avoids introducing undocumented suppression or message-template
    behavior.
-   Makes default behavior deterministic.

------------------------------------------------------------------------

# ADR-027: Reports Reuse Existing Tracking Application Logic

**Status:** Accepted

**Decision**

Implement Reports as an API/reporting layer over existing FleetTrack
application and Traccar integration capabilities rather than creating a
second tracking subsystem.

Current report endpoints cover:

``` text
trips
trip-summary
stops
events
route
summary
hours
combined
```

The Reports controller delegates to existing tracking Actions.
Traccar-backed report reads remain behind `ReportService`.

The Reports controller does not communicate with Traccar directly.

**Rationale**

-   Existing tracking Actions already represent tenant-safe application
    behavior.
-   `ReportService` owns the Traccar report boundary.
-   Existing Resources define normalized public contracts where
    appropriate.
-   Reuse prevents Tracking and Reports from drifting into competing
    definitions of the same data.
-   Reports can add report-specific authorization and validation without
    duplicating GPS/tracking logic.

# ADR-028: Reports Use Capability Permissions Rather Than a Model-Less Report Policy

**Status:** Accepted

**Decision**

Authorize Reports through capability permissions.

Current permissions:

``` text
reports.view
reports.export
```

All implemented report-read endpoints use:

``` text
reports.view
```

A model-less `ReportPolicy` is not required for the current
architecture.

Tenant isolation remains enforced by the application logic that resolves
FleetTrack-visible entities before Traccar data is requested.

`reports.export` remains reserved for export/report generation once its
output contract is defined.

**Rationale**

-   Reports are capabilities rather than persistent Report models.
-   This matches the existing capability-oriented authorization approach
    used by tracking.
-   It avoids creating a policy abstraction without a corresponding
    domain model.
-   Export remains a separate authorization boundary from report reads.

# ADR-029: Report-Specific Request Contracts May Differ from Tracking Contracts

**Status:** Accepted

**Decision**

Reports may use dedicated Form Requests and constraints rather than
being forced to inherit every tracking HTTP validation rule.

The current vehicle-trip report uses `VehicleTripReportRequest` with:

``` text
from    required date
to      required date and after from
```

It still reuses `GetVehicleTrips` and `ReportService` after validation.

**Rationale**

-   Tracking and reporting are different HTTP use cases even when they
    reuse the same application/integration behavior.
-   Reporting may later require different date ranges or export
    constraints.
-   Reusing the Action does not require coupling the HTTP validation
    contracts.
-   Report-specific requirements can evolve without weakening tracking
    limits.

------------------------------------------------------------------------

# ADR-030: External Notification Channels Require Explicit Product Requirements

**Status:** Accepted

**Decision**

Do not add email, push, SMS, or other external Alert-delivery channels
merely because persistent Alerts exist.

Current implemented Alert requirements cover FleetTrack Alert
persistence, history, acknowledgement, custom rules, and supported
Traccar event sources.

External delivery channels should be designed only when product
requirements define:

-   required channels
-   recipients
-   delivery timing
-   retries/failure behavior
-   preferences
-   templates/content
-   audit requirements

**Rationale**

-   Avoids inventing product behavior.
-   Prevents unnecessary infrastructure.
-   Keeps current Alert scope aligned with established requirements.
-   Allows future delivery architecture to be designed around concrete
    needs.

------------------------------------------------------------------------

# ADR-031: Dashboard Reuses Existing Visibility and Connectivity Semantics

**Status:** Accepted

**Decision**

Implement Dashboard overview as an aggregation layer over existing
FleetTrack visibility and tracking behavior.

Dashboard must reuse:

-   Company-scoped model visibility
-   synchronized Device identity through `traccar_device_id`
-   `GetLivePositions`
-   `VehicleOnlineStatus`
-   `Alert::visibleTo($user)`

Dashboard must not define a competing online/offline algorithm.

An online synchronized Device without an assigned Vehicle contributes to
Device connectivity but does not count as an online Vehicle.

**Rationale**

-   Keeps Dashboard numbers consistent with tracking behavior.
-   Prevents duplicate connectivity rules.
-   Preserves tenant isolation at the same application boundaries.
-   Separates Device connectivity from Vehicle connectivity.

------------------------------------------------------------------------

# ADR-032: Time-Based Dashboard KPIs Require an Explicit Reporting Contract

**Status:** Accepted

**Decision**

Treat the current Dashboard overview metrics as the initial fleet KPI
contract.

Do not add distance, duration, speed, utilization, fuel, or similar
time-based KPIs until requirements define the reporting period and
aggregation semantics.

Existing tracking/report Actions may be reused once that contract
exists.

**Rationale**

-   Time-based metrics are meaningless without a defined period.
-   Aggregation rules can materially change KPI interpretation.
-   Existing report capabilities should be reused rather than
    duplicated.
-   Avoids inventing product behavior merely to populate Dashboard
    fields.

------------------------------------------------------------------------

# Current Architecture Baseline

Implemented:

-   Authentication
-   Authorization and company-based multi-tenancy
-   Companies, Fleets, Users, Drivers, Vehicles, and Devices
-   Traccar Device synchronization
-   Live Tracking and per-Vehicle position reads
-   Online/offline tracking status
-   Vehicle position history
-   Aggregate Vehicle trip summary
-   Traccar-detected Vehicle trip history
-   Geofence CRUD and tenant authorization
-   Traccar Geofence lifecycle synchronization
-   Geofence ↔ Vehicle associations
-   Traccar Geofence ↔ Device permission synchronization
-   Geofence/Device association reconciliation
-   Secure Traccar webhook authentication and supported event handling
-   Persistent Alerts and acknowledgement
-   Custom Alert Rule CRUD and runtime evaluation
-   Reports read endpoints for trips, trip summary, stops, events,
    route, summary, hours, and combined
-   Dashboard overview with fleet, connectivity, Device, and Alert
    metrics

Current Reports endpoints:

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

Current Dashboard endpoint:

``` text
GET /api/v1/dashboard/overview
```

The latest reported complete quality gate was green:

``` bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

# Current Roadmap

The currently defined backend fleet-management, tracking, Geofence,
Alerts, Reports-read, and Dashboard overview functionality is
implemented.

The remaining explicit Reports capability is export/report generation
through `reports.export`.

Its format and contract must be defined before implementation.

After that requirement is defined, continue with final integration and
documentation hardening. Additional time-based Dashboard KPIs, external
Alert delivery channels, or persistent FleetTrack Trip business data
should only be added from explicit product requirements.
