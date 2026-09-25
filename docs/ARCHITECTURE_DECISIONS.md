# FleetTrack Architecture Decisions

This document records durable architectural decisions for FleetTrack.

It is not a feature checklist. Current implementation status belongs in
`FEATURES.md`, system structure belongs in `ARCHITECTURE.md`, and
development continuation details belong in `AGENTS.md`.

The decisions below reflect the current `FleetTrack_Laravel(5).zip`
project checkpoint.

------------------------------------------------------------------------

# ADR-001 --- FleetTrack Owns the Business Domain

## Decision

FleetTrack is the authoritative system for:

-   Companies
-   Users
-   roles and permissions
-   Fleets
-   Drivers
-   Vehicles
-   local Device ownership/association
-   local Geofences
-   Geofence ↔ Vehicle relationships
-   Alerts
-   Alert Rules
-   tenancy
-   authorization
-   user-facing application behavior

Traccar is an integration dependency, not the FleetTrack business-domain
authority.

## Consequences

FleetTrack IDs and relationships remain the primary application
identifiers.

Traccar IDs are stored only where required for synchronization and
external reads.

Authorization must always be resolved through FleetTrack before external
tracking data is exposed.

------------------------------------------------------------------------

# ADR-002 --- Traccar Owns GPS Tracking and Trip Detection

## Decision

Traccar remains responsible for:

-   GPS positions
-   detected trips
-   tracking reports
-   Traccar Devices
-   Traccar Geofences
-   Device/Geofence permissions
-   supported tracking events

FleetTrack will not implement a competing GPS trip-detection algorithm.

## Consequences

Trip history and tracking reports are obtained through Traccar and
translated through FleetTrack application contracts.

A persistent FleetTrack Trip entity is not currently required.

If future business requirements require locally persisted Trips, that
should be introduced as a deliberate architectural change rather than by
duplicating Traccar behavior incrementally.

------------------------------------------------------------------------

# ADR-003 --- Keep Laravel Controllers Thin

## Decision

Controllers coordinate HTTP behavior but do not own substantial business
logic.

The standard backend flow is:

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
Resource
```

## Consequences

Controllers may:

-   authorize
-   obtain the authenticated user
-   consume validated data
-   parse validated values
-   invoke Actions
-   return Resources/responses

Controllers should not contain raw Traccar HTTP calls, complex tenancy
logic, Alert Rule evaluation, synchronization orchestration, or GPS
algorithms.

------------------------------------------------------------------------

# ADR-004 --- HTTP Validation Lives in Form Requests

## Decision

HTTP request validation belongs in Laravel Form Requests.

## Consequences

Actions receive already validated input where possible.

Domain invariants may still be enforced in Actions/models when they are
not merely HTTP input rules.

This keeps validation reusable and controllers small.

------------------------------------------------------------------------

# ADR-005 --- Application Operations Live in Actions

## Decision

Application/business operations are represented by Action classes.

## Consequences

CRUD and domain workflows remain reusable outside a single controller.

Actions can coordinate Models, Services, Events, and other application
behavior without coupling that behavior to HTTP.

Existing modules should continue the established Action pattern.

------------------------------------------------------------------------

# ADR-006 --- External Traccar Communication Is Isolated Behind Services

## Decision

FleetTrack communicates with Traccar through dedicated integration
services.

Representative services include:

``` text
TraccarClient
TraccarDeviceService
TraccarGeofenceService
PositionService
ReportService
```

## Consequences

Controllers, Vue pages, and domain models do not call Traccar directly.

Traccar HTTP details can be tested/faked independently.

External payload changes remain contained behind the integration
boundary.

------------------------------------------------------------------------

# ADR-007 --- Use DTOs at the Traccar Boundary

## Decision

Where external Traccar payloads represent meaningful structures,
FleetTrack translates them through dedicated DTOs.

Representative DTOs include Device, Geofence, and supported event data
structures.

## Consequences

External payload shape is prevented from leaking throughout the
application.

FleetTrack code can use stable typed structures even if Traccar
transport details change.

------------------------------------------------------------------------

# ADR-008 --- External Lifecycle Writes Are Asynchronous Where Appropriate

## Decision

Device and Geofence synchronization writes to Traccar use Events,
Listeners, Queue Jobs, and Services.

Conceptually:

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
Traccar
```

## Consequences

Local FleetTrack writes are not unnecessarily blocked by temporary
Traccar availability.

Synchronization becomes eventually consistent.

Queue work must therefore be idempotent and resilient to stale state.

------------------------------------------------------------------------

# ADR-009 --- Tracking and Reports Reads Remain Synchronous

## Decision

Tracking and report reads that are required to answer the current
request call Traccar synchronously through the appropriate Service.

## Consequences

Live positions, history, Trips, Stops, Events, Route, Summary, Hours,
and combined report data can be returned immediately to the caller.

External read failures may affect the current API request and must
remain contained behind the integration/service boundary.

------------------------------------------------------------------------

# ADR-010 --- Company Tenancy Is Enforced Before External Data Access

## Decision

FleetTrack resolves and authorizes tenant-visible local entities before
using their external Traccar identifiers.

## Consequences

A caller cannot bypass FleetTrack tenancy by supplying or discovering a
Traccar Device ID.

The pattern is:

``` text
authenticated User
 ↓
visible FleetTrack entity
 ↓
authorized operation
 ↓
external Traccar ID
 ↓
Traccar request
```

This applies to Tracking, Reports, Geofences, Alerts, and other
integration behavior.

------------------------------------------------------------------------

# ADR-011 --- Spatie Permission Teams Support Company-Scoped Authorization

## Decision

FleetTrack uses Spatie Laravel Permission with Teams alongside Policies
and explicit permission checks.

## Consequences

Authorization distinguishes both:

-   whether a User has a capability
-   whether the target entity is visible within the relevant Company
    scope

Permissions do not replace tenant visibility, and tenant visibility does
not replace capability checks.

------------------------------------------------------------------------

# ADR-012 --- FleetTrack Models Geofence ↔ Vehicle, Not Geofence ↔ Traccar Device

## Decision

The CRM/business relationship is:

``` text
Geofence ↔ Vehicle
```

The Traccar integration translates the Vehicle to its synchronized
Device when external permissions are required.

## Consequences

The business UI and API remain Vehicle-centric.

FleetTrack does not expose Traccar implementation details as the primary
domain relationship.

The local `geofence_vehicle` relationship remains the desired state.

------------------------------------------------------------------------

# ADR-013 --- Reconcile Relationships After Asynchronous External IDs Become Available

## Decision

Device and Geofence synchronization completion reconciles existing local
Geofence ↔ Vehicle relationships.

## Context

A relationship may be created before either side has received its
Traccar external ID.

## Consequences

Geofence synchronization reconciles associated Vehicles.

Device synchronization reconciles associated Geofences.

Local relationship creation does not need to fail merely because an
external ID is not available yet.

------------------------------------------------------------------------

# ADR-014 --- Queue Jobs Verify Current Desired State

## Decision

Queued Traccar permission work verifies FleetTrack's current local
relationship before changing external permissions.

## Consequences

A delayed attach job does not recreate a relationship that was
subsequently removed.

A delayed detach job does not remove a relationship that was
subsequently recreated.

The local FleetTrack relationship remains authoritative.

------------------------------------------------------------------------

# ADR-015 --- Incoming Traccar Events Are Authenticated and Translated

## Decision

Incoming Traccar events enter through the FleetTrack webhook endpoint
and must pass `VerifyTraccarWebhook`.

Supported external event payloads are translated into FleetTrack
application events/DTOs before business behavior executes.

## Consequences

Raw webhook payload handling is isolated.

Alert generation does not depend on controllers containing
event-specific business logic.

Supported event families currently include Device offline, Geofence
transitions, Ignition changes, and Overspeed.

------------------------------------------------------------------------

# ADR-016 --- Alerts Are Persistent FleetTrack Entities

## Decision

Alerts are persisted in FleetTrack rather than being treated only as
transient Traccar events.

## Consequences

FleetTrack can provide:

-   tenant-safe Alert history
-   Alert detail
-   acknowledgement
-   severity/type metadata
-   links to FleetTrack domain entities
-   custom Alert Rule behavior

Acknowledgement is idempotent.

External event IDs may be used to help prevent duplicate Alert
persistence where available.

------------------------------------------------------------------------

# ADR-017 --- Custom Alert Rules Augment Default Alert Behavior

## Decision

Custom Alert Rules are evaluated through centralized rule resolution and
augment the default Alert pipeline.

## Consequences

Vehicle-specific rules can take precedence over Company-wide rules.

If no applicable custom rule matches, default Alert behavior remains
available.

Custom rules do not require a second independent event-ingestion
pipeline.

------------------------------------------------------------------------

# ADR-018 --- Reports Reuse Tracking Infrastructure

## Decision

The Reports HTTP layer reuses existing Tracking Actions, Resources, and
Traccar report infrastructure rather than duplicating external
integration logic.

## Consequences

Tracking and Reports share authorization-safe and tenant-safe data
access behavior.

Changes to common Traccar report handling do not need to be maintained
in two independent implementations.

------------------------------------------------------------------------

# ADR-019 --- Reports Export Remains Undefined Until Product Requirements Exist

## Decision

The existence of a `reports.export` permission does not define an export
format or workflow.

## Consequences

Do not invent CSV, Excel, PDF, email, background export, or download
behavior without an explicit product contract.

The capability can remain reserved until requirements define:

-   export formats
-   report contents
-   date ranges
-   delivery/download behavior
-   synchronous versus asynchronous generation

------------------------------------------------------------------------

# ADR-020 --- Dashboard Reuses Existing Domain Semantics

## Decision

Dashboard aggregation must reuse existing visibility, Alert, and Vehicle
connectivity semantics rather than defining parallel rules.

## Consequences

Online/offline Vehicle behavior uses the shared `VehicleOnlineStatus`
logic.

Alert totals use existing tenant visibility.

Unsynchronized Devices are not automatically treated as offline
synchronized tracking Devices.

This prevents Dashboard metrics from disagreeing with Tracking and Alert
behavior.

------------------------------------------------------------------------

# ADR-021 --- Time-Based Dashboard KPIs Require an Explicit Contract

## Decision

Distance, duration, utilization, average speed, and similar time-based
metrics are not added until their reporting period and aggregation
semantics are explicitly defined.

## Consequences

The current Dashboard Overview focuses on well-defined counts and
connectivity/Alert state.

The project avoids silently choosing "today", "last 24 hours", "this
week", or another period.

------------------------------------------------------------------------

# ADR-022 --- Vue + Inertia Is the Web Application Architecture

## Decision

The FleetTrack web application uses Vue 3 with TypeScript and Inertia.

Laravel provides web entry routes, while Vue pages implement the
application experience and consume FleetTrack APIs for domain data.

## Consequences

The frontend does not require a separate standalone SPA repository.

The application can share Laravel routing/build infrastructure while
still using reusable Vue components and typed frontend services.

New user-facing pages should extend this architecture.

------------------------------------------------------------------------

# ADR-023 --- Use a Shared Responsive Application Shell

## Decision

Authenticated pages use the shared `AppLayout`, `AppHeader`,
`AppSidebar`, and `AppFooter` application shell.

## Consequences

Desktop and mobile navigation behavior is implemented once.

Current shell behavior includes:

-   fixed desktop sidebar
-   mobile drawer
-   backdrop
-   Escape-key close
-   body scroll locking
-   responsive content spacing
-   shared footer

Feature pages should not implement their own independent application
chrome.

------------------------------------------------------------------------

# ADR-024 --- Use Semantic Frontend Design Tokens

## Decision

Application colors are expressed through semantic tokens defined in
`resources/css/app.css`.

Examples include:

``` text
brand
surface
content
muted
border-default
success
warning
danger
info
```

## Consequences

Feature pages should not choose arbitrary Tailwind palette colors for
application semantics.

Changing the visual system can be performed centrally.

Status meaning remains consistent across pages.

------------------------------------------------------------------------

# ADR-025 --- Reuse Shared Vue UI Components

## Decision

Common frontend controls and states live in the shared UI component
layer.

Current primitives include Buttons, Cards, Inputs, Selects, Textareas,
Checkboxes, Tables, Pagination, Form Fields, Status Badges, and
Loading/Error/Empty states.

## Consequences

New pages should first compose existing primitives.

Feature-specific components should be introduced only when they
represent feature-specific behavior or presentation.

This reduces inconsistent markup and duplicated responsive/accessibility
work.

------------------------------------------------------------------------

# ADR-026 --- Use Wayfinder for Typed Laravel Route Access

## Decision

The Vue frontend uses Laravel Wayfinder-generated route helpers where
appropriate.

## Consequences

Laravel route changes should be followed by:

``` bash
sail artisan wayfinder:generate
```

Generated route/action files must not be manually maintained.

Frontend navigation and feature services should prefer generated helpers
over duplicated hard-coded application URLs where a suitable helper
exists.

------------------------------------------------------------------------

# ADR-027 --- Centralize Frontend API Access

## Decision

Generic authenticated frontend HTTP behavior is centralized in
`resources/js/services/apiClient.ts`.

Feature services call `apiRequest()` instead of independently
implementing authentication and error handling.

## Consequences

The shared API client owns:

-   JSON acceptance
-   bearer-token attachment
-   common API errors
-   `401` token cleanup
-   `ApiError`
-   `204 No Content`

Feature services remain small and domain-focused.

------------------------------------------------------------------------

# ADR-028 --- Centralize Frontend Authentication State

## Decision

Authenticated-user state and session restoration are centralized in
`authState`.

Token persistence is isolated through `authToken`.

## Consequences

Pages and components do not maintain independent copies of
authentication state.

The sidebar can display the real authenticated User.

Refreshing an authenticated page restores the User through `/auth/me`.

Logout clears the shared state consistently.

------------------------------------------------------------------------

# ADR-029 --- Keep the Existing Sanctum Bearer-Token Web Flow

## Decision

The current Vue/Inertia web client uses the existing Sanctum
personal-access-token API flow.

The token is persisted in browser local storage and attached as a bearer
token by the shared API client.

## Context

The backend authentication API already returns personal access tokens,
and the current frontend was integrated with that established contract.

## Consequences

The current web application does not rely on Laravel's session `auth`
middleware for its Inertia routes.

`AppLayout` initializes authentication and redirects unauthenticated
users to `/login`.

Protected API data remains server-side protected by `auth:sanctum`,
Policies, permissions, and tenancy.

A move to cookie/session-based SPA authentication would be a deliberate
authentication redesign. It should not be partially layered on top of
the current bearer-token approach.

------------------------------------------------------------------------

# ADR-030 --- Treat Frontend Route Guarding and API Authorization as Separate Boundaries

## Decision

Frontend route guarding improves application navigation/UX, while
backend API authorization remains the actual data-security boundary.

## Consequences

Hiding or redirecting a Vue page is never considered sufficient
authorization.

Every protected API continues to enforce Sanctum authentication and
application authorization independently of the frontend.

------------------------------------------------------------------------

# ADR-031 --- Feature Services Remain Thin

## Decision

Frontend feature services translate page/domain needs into calls through
the shared API client and generated routes.

## Consequences

A feature service such as `fleetService.ts` should not duplicate generic
token management, global error behavior, or unrelated state management.

Vue pages remain focused on presentation and interaction.

------------------------------------------------------------------------

# ADR-032 --- Separate Backend Completion From Frontend Completion

## Decision

Project documentation and planning distinguish:

``` text
Backend/API implemented
Frontend/UI implemented
Fully integrated user-facing feature
```

## Context

FleetTrack has historically completed backend/API contracts before their corresponding Vue modules. At the 2026-09-25 checkpoint, the previously identified backend-backed frontend gaps have been integrated.

## Consequences

An implemented API does not automatically mean the corresponding product
feature is complete.

This distinction remains important for future features: an API contract, a Vue screen, and a browser-verified integrated workflow are separate completion states and should be documented accurately.

------------------------------------------------------------------------

# ADR-033 --- Responsive Behavior Is Part of Feature Completion

## Decision

Responsive behavior is implemented as each frontend feature is built
rather than deferred to a final cleanup phase.

## Consequences

New pages should be checked at desktop and mobile widths.

Shared responsive primitives and the application shell should be reused.

Tables, forms, dialogs/drawers, actions, and page spacing must remain
usable on smaller screens.

------------------------------------------------------------------------

# ADR-034 --- Generated Code Is Regenerated, Not Hand-Edited

## Decision

Generated Wayfinder route/action files are treated as generated
artifacts.

## Consequences

When backend routes change, run the generator.

Do not fix generated frontend routing output by manually editing
generated files.

The source Laravel route definitions remain authoritative.

------------------------------------------------------------------------

# ADR-035 --- Quality Gates Are Part of Feature Completion

## Decision

A meaningful implementation slice is not considered complete until its
relevant quality gate passes.

Backend/full-stack gate:

``` bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

Frontend gate:

``` bash
npm run format
npm run format:check
npm run lint:check
npm run types:check
npm run build
```

## Consequences

Formatting, static analysis, tests, TypeScript correctness, linting, and
production build failures are fixed before the feature is committed.

Targeted checks can be used during development.

A Git commit message is provided at the meaningful green checkpoint
rather than after every individual file.

------------------------------------------------------------------------

# ADR-036 --- Source Code Is the Final Authority

## Decision

Documentation describes the architecture and status, but the latest
source code remains authoritative when documentation becomes stale.

## Consequences

Before modifying an existing implementation, inspect the current source
snapshot.

Do not invent paths, signatures, component props, helpers, Resources,
routes, permissions, or test utilities from documentation alone.

When a current project ZIP is available, it should be used rather than
repeatedly requesting files already contained in that snapshot.

------------------------------------------------------------------------

# ADR-037 --- Preserve Field Validation Errors and Hide Unexpected Server Details

## Decision

Laravel Form Requests are the authoritative server-side validation boundary for
user-correctable input. Persistence constraints that can be expressed as input
rules should be validated before database writes.

The shared frontend `ApiError` preserves Laravel `422` field errors so Vue forms
can display the relevant message beside the corresponding field. Unexpected
`5xx` response messages are not displayed verbatim to users; the frontend uses
a generic server-error message instead.

## Consequences

-   Form Requests should mirror database uniqueness/nullability/default
    constraints when those constraints are user-correctable.
-   Vue forms should use `FormField` error presentation rather than collapsing
    validation into one generic banner.
-   Correcting a field should clear its stale server-side error.
-   Database exception text, SQL, table names, and internal implementation
    details must not be exposed through normal frontend error presentation.
-   Regression tests should cover previously observed request/database contract
    mismatches.

Current concrete examples are company-scoped Fleet name/code uniqueness, Fleet
timezone normalization, Vehicle VIN uniqueness, and Vehicle odometer
normalization.

------------------------------------------------------------------------

# ADR-038 --- Development Seeds Form a Connected, Repeatable Dataset

## Decision

`DatabaseSeeder` is the canonical local-development reset and must create a coherent multi-tenant dataset after `sail artisan migrate:fresh --seed`.

The system company receives no operational dummy fleet data. Customer-company operational records are connected through real FleetTrack relationships. Seeded Devices and Geofences do not receive fabricated Traccar IDs or synchronization timestamps.

## Consequences

- Fresh development environments have enough data for pagination, filters, CRUD, tracking-adjacent UI, geofences, alerts, rules, reports, and dashboard work.
- Each seeded Vehicle has a local Device in the same company.
- Drivers are assigned to seeded Fleets.
- Geofences have Vehicle associations.
- Alert Rules and Alerts cover representative supported event types.
- External synchronization state remains truthful: null until a real Traccar synchronization occurs.

# ADR-039 --- Use Leaflet for the Live Tracking Map

## Decision

The current web Live Tracking map uses Leaflet with OpenStreetMap tiles. Tracking data remains supplied by FleetTrack's Laravel API; the map library is a presentation concern and does not become a tracking data source.

## Consequences

- `LiveTrackingMap.vue` owns the Leaflet map and marker layer lifecycle.
- Online/offline state is rendered from FleetTrack tracking status, not inferred by Leaflet.
- The map fits bounds on initial render or when the rendered vehicle set changes, but ordinary polling updates do not continually reset user pan/zoom.
- Vehicle tooltips/popups are built from the typed `LivePosition` payload.
- Leaflet and `@types/leaflet` are explicit frontend dependencies.

------------------------------------------------------------------------

# ADR-040 --- Poll Current Live Positions at a Modest Interval

## Decision

The current Live Tracking page refreshes positions every 30 seconds and also supports manual refresh. It prevents a new polling request from starting while a previous positions request is still pending.

## Consequences

- Polling is started when the Tracking page mounts and cleared before unmount.
- Background refresh does not replace the page with a full loading state.
- Fleet/vehicle filter changes trigger an immediate filtered refresh.
- A future real-time transport may replace polling if product requirements justify it; the current API/service boundary should remain reusable.

------------------------------------------------------------------------

# ADR-041 --- Super Admin Must Explicitly Select Geofence Ownership

## Decision

Geofence creation by a Super Admin requires an explicit `company_id`, matching `StoreGeofenceRequest`. The Vue form exposes a Company selector only for the actual `super_admin` role value. Company Admin requests continue to rely on backend tenancy behavior.

## Consequences

- Do not silently choose a company for a Super Admin when multiple companies are available.
- Frontend authorization/visibility checks must use stored enum values, not display-normalized role labels.
- `companyService.ts` and `company.ts` provide the minimal frontend Company API/type support currently needed by Geofences.
- The system company remains excluded by the existing Company index query.

------------------------------------------------------------------------

# ADR-042 --- Local Traccar HTTP Fakes Are Development Infrastructure

## Decision

In the `local` environment, `AppServiceProvider` registers `LocalTraccarServiceProvider` to fake the Traccar HTTP interactions needed by current local UI development. This provider is not registered as production behavior.

## Consequences

- Current local fake coverage includes positions, geofence CRUD, and permission attach/detach.
- Generated position data is based on local Devices with Traccar device IDs.
- Fake Geofence records are held in memory and are not durable Traccar state.
- Tests and production code must not depend on the local fake as an external persistence mechanism.
- Additional fake endpoints should be added deliberately as local UI work reaches other Traccar-backed features.

------------------------------------------------------------------------

# Current Decision-Driven Development Direction

Completed active frontend work now includes:

```text
Fleets → Vehicles → Drivers → Devices
→ Live Tracking + position history
→ Geofences + vehicle assignments + map rendering
→ Alert Rules → Alerts → Reports → Dashboard overview integration
```

The previously identified backend-backed frontend gaps are complete. The next product-facing work should be selected from explicit requirements rather than the old backlog. Scope-dependent candidates include interactive Geofence boundary drawing/editing, report exports, standalone Company administration, additional tracking/trip UX, and a deliberate real-Traccar development/demo workflow.

Every new frontend module continues to reuse `AppLayout`, shared UI components, semantic design tokens, `authState`, `apiRequest()`, feature services/types, Wayfinder routes, and existing Laravel authorization/API contracts.
