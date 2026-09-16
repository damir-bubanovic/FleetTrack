# FleetTrack Architecture

## 1. Purpose

This document describes the current backend architecture of FleetTrack
at the latest completed development checkpoint.

FleetTrack is a multi-tenant Laravel fleet-management application that
uses Traccar as the external GPS/tracking engine. FleetTrack owns
business-domain data and authorization. Traccar owns GPS positions,
detected trips, and the tracking-specific representation of devices,
geofences, and their permissions.

The architecture is designed around:

-   strict company tenancy
-   thin HTTP controllers
-   Form Requests for input validation
-   Policies and permission gates for authorization
-   Actions for application/business logic
-   API Resources for response contracts
-   Services and DTOs for Traccar integration
-   Events, Listeners, and Queue Jobs for asynchronous writes
-   synchronous Traccar reads for tracking and reporting
-   Pest feature tests
-   PHPStan / Larastan
-   Laravel Pint

The current backend includes the core fleet-management modules,
tracking, Geofences, Alerts, Custom Alert Rules, and the first Reports
API slice.

------------------------------------------------------------------------

# 2. Technology

Core stack:

``` text
PHP ^8.3
Laravel ^13.17
Laravel Sanctum
Spatie Laravel Permission with Teams
MySQL
Redis
Laravel Sail
Pest
PHPStan / Larastan
Laravel Pint
Traccar REST API
```

------------------------------------------------------------------------

# 3. High-Level System Architecture

``` text
Mobile / Web Client
        |
        v
FleetTrack Laravel API
        |
        +-----------------------------+
        |                             |
        v                             v
FleetTrack Database             Traccar Server
        |                             |
        |                             +--> GPS positions
        |                             +--> GPS trip detection
        |                             +--> Traccar devices
        |                             +--> Traccar geofences
        |                             +--> Device/geofence permissions
        |                             +--> Tracking events
        |
        +--> Companies
        +--> Users / roles / permissions
        +--> Fleets
        +--> Drivers
        +--> Vehicles
        +--> Devices
        +--> Geofences
        +--> Geofence/Vehicle associations
        +--> Alerts
        +--> Alert Rules
```

FleetTrack remains the business-domain authority.

Traccar remains the tracking/GPS authority.

FleetTrack does not expose Traccar identifiers as a substitute for its
own authorization model.

------------------------------------------------------------------------

# 4. Application Layers

The primary synchronous HTTP flow is:

``` text
Route
  ↓
Middleware
  ↓
Form Request
  ↓
Controller
  ↓
Policy / Permission Gate
  ↓
Action
  ↓
Model / Service
  ↓
API Resource
  ↓
JSON Response
```

Asynchronous external synchronization adds:

``` text
Action
  ↓
Domain Event
  ↓
Listener
  ↓
Queue Job
  ↓
Traccar Service
  ↓
Traccar REST API
```

Incoming Traccar events use:

``` text
Traccar
  ↓
Webhook
  ↓
VerifyTraccarWebhook
  ↓
TraccarEventController
  ↓
Event-specific DTO / Handler
  ↓
Application Event
  ↓
Queued Listener
  ↓
Alert Action
  ↓
AlertRule Resolver
  ↓
Alert persistence
```

------------------------------------------------------------------------

# 5. Controllers

Controllers are intentionally thin.

Their responsibilities are limited to:

-   receiving validated requests
-   invoking authorization
-   resolving the authenticated user
-   converting request values into required value objects where
    appropriate
-   calling Actions
-   returning API Resources or HTTP responses

Controllers should not contain:

-   Traccar HTTP implementation
-   tenant ownership mutation logic
-   synchronization orchestration
-   complex alert-rule evaluation
-   GPS trip-detection logic

Examples include:

``` text
Api\Auth\AuthController
Api\Company\CompanyController
Api\Fleet\FleetController
Api\Driver\DriverController
Api\Vehicle\VehicleController
Api\Device\DeviceController
Api\Geofence\GeofenceController
Api\Geofence\GeofenceVehicleController
Api\Tracking\LiveTrackingController
Api\Traccar\TraccarEventController
Api\Alert\AlertController
Api\AlertRule\AlertRuleController
Api\Report\ReportController
```

------------------------------------------------------------------------

# 6. Form Requests

Form Requests define input validation.

Current examples include requests for:

-   Companies
-   Fleets
-   Drivers
-   Vehicles
-   Devices
-   Geofences
-   Alert Rules
-   Tracking date ranges
-   Report date ranges

The first Reports endpoint uses a dedicated `VehicleTripReportRequest`.

Its current contract requires:

``` text
from    required date
to      required date and after from
```

Validation remains separate from Actions and controllers.

------------------------------------------------------------------------

# 7. Actions

Actions contain application/business logic.

Examples of responsibilities include:

-   enforcing company ownership
-   preventing tenant transfer
-   resolving related models
-   creating/updating/deleting domain models
-   initiating synchronization events
-   retrieving visible tracking data
-   resolving custom Alert Rules
-   generating Alerts

Representative areas:

``` text
Actions/
├── Alert/
├── AlertRule/
├── Company/
├── Device/
├── Driver/
├── Fleet/
├── Geofence/
├── Tracking/
└── Vehicle/
```

The Reports layer currently reuses the existing tracking Action
`GetVehicleTrips` instead of introducing duplicate reporting logic.

------------------------------------------------------------------------

# 8. API Resources

API Resources define stable response contracts.

Resources isolate external or model-specific representation from the
HTTP contract.

Current examples include resources for:

-   Companies
-   Fleets
-   Drivers
-   Vehicles
-   Devices
-   Geofences
-   Tracking positions
-   Vehicle trip summaries
-   Vehicle trips
-   Alerts
-   Alert Rules

The Reports vehicle-trip endpoint reuses `VehicleTripResource`, ensuring
that tracking and reporting expose the same normalized trip
representation.

------------------------------------------------------------------------

# 9. Authentication

Laravel Sanctum protects authenticated API routes.

Primary authentication endpoints:

``` text
POST /api/v1/auth/login
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

The Traccar event webhook is intentionally outside Sanctum because
Traccar is the caller.

Instead it is protected by:

``` text
VerifyTraccarWebhook
```

------------------------------------------------------------------------

# 10. Authorization and Multi-Tenancy

FleetTrack uses company-based tenancy.

Spatie Laravel Permission is configured with Teams.

Authenticated tenant-aware routes use:

``` text
auth:sanctum
SetPermissionTeam
```

The middleware establishes the correct permission-team context for the
authenticated company user.

## Authorization mechanisms

FleetTrack uses both:

1.  model Policies
2.  permission gates for capability-oriented endpoints

Examples:

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

Model-specific CRUD modules use Policies where ownership checks are
required.

## Tenant visibility

Models that belong to companies use company visibility patterns,
including the shared `BelongsToCompany` concern.

The concern provides company relationship and visibility/query helpers
such as:

``` text
company()
forCompany()
visibleTo()
```

Core rule:

``` text
Company user
    ↓
may access only entities visible to that company
```

Super Administrators may have global visibility where explicitly
supported.

External Traccar IDs never override this rule.

------------------------------------------------------------------------

# 11. Core Domain Model

The current domain can be represented approximately as:

``` text
Company
├── Users
├── Fleets
├── Drivers
├── Vehicles
│   ├── Device
│   ├── Alerts
│   ├── Alert Rules (optional vehicle scope)
│   └── Geofences (many-to-many)
├── Devices
├── Geofences
├── Alerts
└── Alert Rules
```

A Geofence belongs to one Company.

A Vehicle belongs to one Company and may belong to a Fleet.

A Device belongs to a Company and is assigned to a Vehicle according to
the existing Device domain rules.

A Geofence may be associated with multiple Vehicles.

A Vehicle may be associated with multiple Geofences.

An Alert belongs to a Company and is associated with the relevant
vehicle/event context.

An Alert Rule belongs to a Company and may optionally be scoped to a
Vehicle.

------------------------------------------------------------------------

# 12. Traccar Boundary

All Traccar HTTP communication is isolated behind dedicated integration
classes.

Core services include:

``` text
TraccarClient
TraccarDeviceService
TraccarGeofenceService
PositionService
ReportService
```

DTOs isolate Traccar payloads from FleetTrack application code.

Examples include:

``` text
DeviceData
GeofenceData
GeofenceEventData
OverspeedEventData
IgnitionEventData
DeviceOfflineEventData
```

This boundary prevents controllers and domain models from becoming
coupled to Traccar's raw HTTP API.

------------------------------------------------------------------------

# 13. Device Synchronization

Device lifecycle writes are asynchronous.

``` text
Device Action
    ↓
Device Event
    ↓
Listener
    ↓
Queue Job
    ↓
TraccarDeviceService
    ↓
Traccar REST API
```

Implemented behavior includes:

-   create Traccar Device
-   update Traccar Device
-   delete Traccar Device
-   persist Traccar Device ID
-   persist synchronization timestamp/state
-   retry failed queue work
-   reconcile existing Geofence associations after initial Device
    synchronization

The local FleetTrack write is not coupled to immediate Traccar
availability.

------------------------------------------------------------------------

# 14. Geofence Architecture

The Geofence module contains four major concerns:

1.  local CRUD and authorization
2.  Traccar lifecycle synchronization
3.  Geofence ↔ Vehicle association synchronization
4.  Traccar geofence transition event handling

## Local Geofence lifecycle

``` text
API
 ↓
GeofenceController
 ↓
Policy
 ↓
Create / Update / Delete Geofence Action
 ↓
Geofence model
```

Tenant rules prevent company users from creating or transferring
Geofences outside their own Company.

Traccar-managed fields are not writable by ordinary API clients.

## Traccar lifecycle synchronization

``` text
Geofence Action
 ↓
Domain Event
 ↓
Listener
 ↓
Queue Job
 ↓
TraccarGeofenceService
 ↓
Traccar REST API
```

The local Geofence stores the external Traccar geofence ID after
successful synchronization.

------------------------------------------------------------------------

# 15. Geofence ↔ Vehicle Association Architecture

FleetTrack intentionally models:

``` text
Geofence ↔ Vehicle
```

rather than exposing a Traccar-specific:

``` text
Geofence ↔ Device
```

relationship to the CRM domain.

The local association is stored in the `geofence_vehicle` pivot table.

Synchronization resolves:

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

Then FleetTrack synchronizes the resulting Traccar permission.

## Attach flow

``` text
POST /geofences/{geofence}/vehicles/{vehicle}
 ↓
GeofenceVehicleController
 ↓
Geofence update authorization
 ↓
AttachVehicleToGeofence
 ↓
geofence_vehicle pivot
 ↓
VehicleAttachedToGeofence
 ↓
Listener
 ↓
SyncGeofenceVehiclePermission
 ↓
Traccar POST /permissions
```

## Detach flow

``` text
DELETE /geofences/{geofence}/vehicles/{vehicle}
 ↓
GeofenceVehicleController
 ↓
Geofence update authorization
 ↓
DetachVehicleFromGeofence
 ↓
geofence_vehicle pivot removal
 ↓
VehicleDetachedFromGeofence
 ↓
Listener
 ↓
RemoveGeofenceVehiclePermission
 ↓
Traccar DELETE /permissions
```

Attach and detach behavior is idempotent.

Cross-company associations are rejected.

------------------------------------------------------------------------

# 16. Eventual Consistency and Stale Queue Protection

Device and Geofence synchronization is asynchronous.

Therefore this sequence is valid:

``` text
Create local Geofence
 ↓
Attach Vehicle
 ↓
Geofence Traccar ID does not exist yet
 ↓
Initial permission job cannot synchronize
 ↓
Geofence synchronization completes later
 ↓
Existing Vehicle associations are reconciled
```

The inverse is also valid when the Device receives its Traccar ID later.

Both sides therefore participate in reconciliation:

``` text
Geofence sync completion
    ↓
reconcile associated Vehicles

Device sync completion
    ↓
reconcile associated Geofences
```

## Stale attach protection

Before creating a Traccar permission, the queued job checks that the
local Geofence ↔ Vehicle association still exists.

If it was removed after the job was queued, the job exits without
writing stale state.

## Stale detach protection

Before deleting a Traccar permission, the queued job checks that the
local association has not been recreated.

If it exists again, the stale detach job exits.

This makes the local FleetTrack relationship the desired-state
authority.

------------------------------------------------------------------------

# 17. Traccar Event Architecture

FleetTrack receives supported Traccar events through:

``` text
POST /api/v1/traccar/events
```

The endpoint is protected by `VerifyTraccarWebhook`.

Current event types:

``` text
geofenceEnter
geofenceExit
deviceOverspeed
ignitionOn
ignitionOff
deviceOffline
```

The controller validates the event-specific payload and delegates to the
corresponding handler.

Typical flow:

``` text
Traccar
 ↓
TraccarEventController
 ↓
Event DTO
 ↓
Handle<Event>
 ↓
FleetTrack application event
 ↓
Queued listener
 ↓
Create<Event>Alert
 ↓
AlertRule resolution
 ↓
Alert persistence
```

This prevents raw Traccar payload handling from leaking into Alert
Actions.

------------------------------------------------------------------------

# 18. Alert Architecture

Alerts are persistent FleetTrack business entities.

Current supported sources:

-   Geofence entry
-   Geofence exit
-   Overspeed
-   Ignition on
-   Ignition off
-   Device offline

The Alerts API provides:

``` text
GET   /api/v1/alerts
GET   /api/v1/alerts/{alert}
PATCH /api/v1/alerts/{alert}/acknowledge
```

Authorization uses:

``` text
alerts.view
alerts.acknowledge
```

Alerts are company-scoped and tenant-safe.

Alert acknowledgement is idempotent.

Where a Traccar event ID is available, alert creation uses it as part of
the idempotency strategy to avoid duplicate persisted alerts for the
same external event.

------------------------------------------------------------------------

# 19. Custom Alert Rule Architecture

Alert Rules provide configurable behavior without replacing the default
alert pipeline.

An Alert Rule contains:

``` text
company_id
vehicle_id nullable
name
type
severity
conditions JSON
is_active
```

Meaning:

``` text
vehicle_id = null
    → company-wide rule

vehicle_id = value
    → vehicle-specific rule
```

Supported rule types currently match the implemented Alert sources:

``` text
overspeed
geofence_enter
geofence_exit
ignition_on
ignition_off
device_offline
```

## Rule resolution

Runtime rule evaluation is centralized in:

``` text
ResolveAlertRule
```

Conceptual flow:

``` text
Alert event
 ↓
Resolve Vehicle
 ↓
ResolveAlertRule
 ↓
active rules for Company + event type
 ↓
vehicle-specific rules first
 ↓
company-wide fallback
 ↓
condition evaluation
 ↓
matching rule or null
```

For overspeed rules:

``` text
conditions.speed_limit_kmh
```

is compared against the actual event speed converted to km/h.

The comparison is strict:

``` text
actual speed > configured limit
```

A speed equal to the configured threshold does not match.

If a vehicle-specific rule does not satisfy its condition, a matching
company-wide rule may still be selected.

For the other currently supported event types, no additional runtime
condition is required.

If a matching rule exists, its severity overrides the default Alert
severity.

If no rule matches, the existing default Alert behavior remains
unchanged.

This fallback is important: custom rules augment Alert generation rather
than silently disabling default Alerts.

------------------------------------------------------------------------

# 20. Tracking Read Architecture

Tracking reads are synchronous.

The client needs the current Traccar response, so they do not use queue
jobs.

``` text
Tracking API
 ↓
LiveTrackingController
 ↓
Tracking Action
 ↓
PositionService / ReportService
 ↓
Traccar REST API
 ↓
Tracking API Resource
 ↓
JSON
```

Tracking routes require authentication, permission-team middleware, and
`tracking.view` authorization.

------------------------------------------------------------------------

# 21. Live Position Tracking

Fleet-wide live tracking:

``` text
GET /api/v1/tracking/positions
```

Per-vehicle live tracking:

``` text
GET /api/v1/tracking/vehicles/{vehicle}
```

FleetTrack first resolves visible FleetTrack entities and synchronized
Devices. Only then are Traccar position requests made.

This ensures external tracking data cannot bypass FleetTrack tenancy.

Online/offline status is derived from the freshness of the latest GPS
fix.

------------------------------------------------------------------------

# 22. Position History

Endpoint:

``` text
GET /api/v1/tracking/vehicles/{vehicle}/positions
```

Flow:

``` text
Vehicle
 ↓
tenant-visible Device
 ↓
traccar_device_id
 ↓
PositionService
 ↓
Traccar positions API
 ↓
HistoricalPositionResource
```

The tracking request validates a bounded date range.

The current position-history contract allows a maximum seven-day range.

------------------------------------------------------------------------

# 23. Aggregate Vehicle Trip Summary

Endpoint:

``` text
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
```

This endpoint is calculated from the requested position-history range.

It provides aggregate statistics such as:

-   position count
-   start/end time
-   duration
-   distance
-   average speed
-   maximum speed
-   moving time
-   stopped time

It is intentionally distinct from Traccar-detected trip history.

The summary answers:

``` text
What happened across this selected position range?
```

It does not answer:

``` text
Which individual GPS trips did Traccar detect?
```

------------------------------------------------------------------------

# 24. Traccar-Detected Vehicle Trips

Tracking endpoint:

``` text
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

FleetTrack delegates GPS trip detection to Traccar's:

``` text
/reports/trips
```

The flow is:

``` text
Vehicle
 ↓
GetVehicleTrips
 ↓
tenant-visible synchronized Device
 ↓
ReportService
 ↓
Traccar /reports/trips
 ↓
VehicleTripResource
```

FleetTrack does not currently maintain a competing trip-detection
algorithm.

The normalized trip contract includes:

-   device reference
-   optional driver unique reference
-   start/end timestamps
-   start/end coordinates
-   distance in kilometers
-   duration
-   average speed
-   maximum speed
-   explicit speed unit
-   start/end addresses

------------------------------------------------------------------------

# 25. Reports Architecture

Reports is a report-specific HTTP layer over existing FleetTrack
tracking/application behavior.

Architectural rule:

``` text
Report-specific HTTP API
    ↓
reuse existing FleetTrack tracking Actions
    ↓
reuse Traccar service boundary
    ↓
reuse stable API Resources where appropriate
```

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

Route middleware:

``` text
api
auth:sanctum
SetPermissionTeam
```

Controller authorization:

``` text
reports.view
```

The controller delegates to existing tracking Actions:

-   `GetVehicleTrips`
-   `GetVehicleTripSummary`
-   `GetVehicleStops`
-   `GetVehicleEvents`
-   `GetVehicleRoute`
-   `GetVehicleSummary`
-   `GetVehicleHours`
-   `GetVehicleCombinedReport`

Traccar report reads remain behind `ReportService`, which currently owns
the `/reports/trips`, `/reports/stops`, `/reports/events`,
`/reports/route`, `/reports/summary`, `/reports/hours`, and
`/reports/combined` boundaries.

The Reports controller does not perform Traccar HTTP calls directly.

Stable FleetTrack Resources normalize report output where a contract has
been established. The combined report intentionally preserves the raw
combined payload rather than inventing a narrower schema.

Focused report coverage includes authentication, authorization,
validation, tenant isolation, Super Administrator access, missing or
unsynchronized Devices, Traccar request parameters, and report response
contracts.

# 26. Reports Permissions

Report permissions already exist in the authorization model:

``` text
reports.view
reports.export
```

The current trip report uses:

``` text
reports.view
```

`reports.export` is reserved for export functionality when the output
contract is defined.

A model-less `ReportPolicy` is not required for the current
architecture. Capability authorization is performed through Laravel's
permission gate, in the same general style as tracking capability
authorization.

------------------------------------------------------------------------

# 27. Reports Work Remaining

Reports read endpoints are implemented.

The remaining reserved capability is export/report generation through:

``` text
reports.export
```

The export architecture must not be finalized until product requirements
define the output format, included data, validation, and
response/download contract.

No duplicate GPS trip-detection algorithm should be introduced.

# 28. Dashboard

Dashboard overview is implemented as a thin aggregation layer over
existing FleetTrack domain visibility and tracking semantics.

Endpoint:

``` text
GET /api/v1/dashboard/overview
```

Flow:

``` text
DashboardController
 ↓
GetDashboardOverview
 ├─ Company counts
 ├─ visible synchronized Devices
 ├─ visible Alerts
 └─ GetLivePositions
      ↓
   VehicleOnlineStatus
 ↓
DashboardOverviewResource
```

Current response metrics:

``` text
companies
fleets
vehicles
devices
online_vehicles
offline_vehicles
offline_devices
alerts
unacknowledged_alerts
```

Architectural rules:

-   Dashboard does not create a second connectivity definition.
-   Online/offline freshness is centralized in `VehicleOnlineStatus`.
-   Device connectivity is based on synchronized Devices with non-null
    `traccar_device_id`.
-   Unsynchronized Devices are not classified as offline tracking
    Devices.
-   Unassigned online Devices may contribute to Device connectivity but
    not Vehicle connectivity.
-   Alert aggregation reuses `Alert::visibleTo($user)`.
-   Unacknowledged Alerts are represented by null `acknowledged_at`.
-   Super Administrator customer totals exclude the internal system
    Company.

The current metrics are the initial fleet KPI contract. Time-based
distance, duration, speed, utilization, or similar KPIs require an
explicit reporting period and aggregation contract before they are
added.

# 29. Error and Failure Boundaries

FleetTrack distinguishes between local business state and external
Traccar state.

## Local writes

Local model changes can succeed before external synchronization
completes.

Queue jobs provide eventual synchronization.

## External read failures

Tracking and Reports are synchronous reads, so Traccar failures can
affect the current API request.

Those failures remain behind the Traccar service layer.

## External webhook failures

Traccar webhook requests must pass webhook authentication and payload
validation before application events are dispatched.

## Tenant failures

FleetTrack authorization and visibility checks happen before external
tracking data is exposed.

------------------------------------------------------------------------

# 30. Testing Architecture

FleetTrack uses Pest.

Testing is organized around behavior rather than relying only on broad
end-to-end coverage.

Current test areas include:

-   API endpoints
-   Actions
-   Policies
-   Form Requests
-   Models and relationships
-   Traccar services
-   Queue Jobs
-   Listeners
-   Traccar event handling
-   Alert generation
-   Alert Rule resolution
-   Reports

External Traccar HTTP calls are faked in tests.

The first Reports feature suite is:

``` text
tests/Feature/Report/VehicleTripReportApiTest.php
```

It contains eight passing tests at the current checkpoint.

------------------------------------------------------------------------

# 31. Development Quality Gate

FleetTrack uses Composer scripts and Sail.

Formatting:

``` bash
sail composer lint
```

Formatting verification:

``` bash
sail composer lint:check
```

Static analysis:

``` bash
sail composer types:check
```

Tests:

``` bash
sail artisan test
```

The expected quality gate before committing a meaningful functionality
slice is:

``` bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

Do not use:

``` text
sail artisan lint
```

All failures should be fixed before committing the slice.

------------------------------------------------------------------------

# 32. Development Workflow

The established development workflow is:

1.  Work through one implementation concern/file at a time.
2.  Explain the intended change before or while implementing it.
3.  Prefer Sail and Artisan generators where appropriate.
4.  Avoid running the entire quality gate after every individual file.
5.  Run targeted tests while developing a focused behavior.
6.  At a meaningful feature/section boundary, run the full quality gate.
7.  Fix all failures before proceeding.
8.  Commit the completed green slice.
9.  Continue to the next slice.

This keeps feedback fast without sacrificing commit-level quality.

------------------------------------------------------------------------

# 33. Current Architectural Checkpoint

The current backend architecture includes:

``` text
Authentication / Authorization / Multi-Tenancy
 ↓
Companies / Fleets / Drivers / Vehicles / Devices
 ↓
Traccar Device synchronization
 ↓
Live Tracking / Position History
 ↓
Aggregate Trip Summary / Traccar Trip History
 ↓
Geofences / Traccar Geofence synchronization
 ↓
Geofence ↔ Vehicle permission synchronization
 ↓
Secure supported Traccar event ingestion
 ↓
Alerts / Acknowledgement / Custom Alert Rules
 ↓
Reports read API
 ↓
Dashboard Overview / Initial Fleet KPIs
```

Reports currently exposes:

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

Dashboard currently exposes:

``` text
GET /api/v1/dashboard/overview
```

The latest reported quality gate was green:

``` bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

The remaining explicit Reports capability is export/report generation
through `reports.export`; its output contract is not yet defined.

# 34. Next Architectural Work

The next architectural work should be driven by explicit remaining
product requirements rather than by speculative backend expansion.

Current unresolved contract:

``` text
Define Reports export format/content
 ↓
Implement reports.export when defined
 ↓
Final integration/documentation hardening
```

If additional Dashboard KPIs are requested, first define their reporting
period and aggregation semantics, then reuse existing tracking/report
Actions where possible.

External Alert delivery and a persistent FleetTrack Trip entity remain
future product decisions rather than current architectural requirements.

# 35. Architectural Principles

The current architecture should continue to follow these rules:

1.  FleetTrack owns business-domain state and authorization.
2.  Traccar owns GPS tracking data and GPS trip detection.
3.  Controllers stay thin.
4.  Validation belongs in Form Requests.
5.  Business/application logic belongs in Actions.
6.  External Traccar communication belongs in dedicated Services.
7.  Traccar payload translation belongs in DTOs.
8.  External writes use Events, Listeners, and Queue Jobs where eventual
    consistency is appropriate.
9.  Synchronous tracking/report reads remain synchronous when the caller
    needs the result immediately.
10. Company tenancy is enforced before external data is exposed.
11. Local Geofence ↔ Vehicle state is the desired-state authority for
    Traccar permission synchronization.
12. Queue jobs must protect against stale desired state.
13. Asynchronous external-ID dependencies must be reconciled after
    synchronization.
14. Incoming Traccar webhooks must be authenticated and translated
    before application behavior is triggered.
15. Custom Alert Rules augment default alert behavior rather than
    replacing the default pipeline when no rule matches.
16. Reports reuse tracking/application infrastructure instead of
    duplicating it.
17. Dashboard reuses existing visibility, Alert, and connectivity
    semantics instead of defining parallel rules.
18. Time-based Dashboard KPIs require an explicit reporting period and
    aggregation contract.
19. Stable output contracts belong in API Resources.
20. New behavior receives focused tests.
21. Meaningful commits must pass formatting, static analysis, and the
    full test suite.
22. The latest source code is authoritative when documentation and
    implementation diverge.
