# FleetTrack Architecture

## 1. Purpose

This document describes the current FleetTrack architecture at the
`FleetTrack_Laravel(7).zip` checkpoint.

FleetTrack is a multi-tenant fleet-management and GPS-tracking
application. Laravel owns the business domain, tenancy, authorization,
API contracts, and application workflows. Traccar remains the external
GPS/tracking engine and owns GPS positions, GPS trip detection,
Traccar-side Devices and Geofences, tracking permissions, and supported
tracking events.

The project now has two established application layers:

``` text
Laravel backend/API
Vue 3 + Inertia frontend
```

The frontend is no longer only a placeholder. It has a reusable
application shell, semantic design system, authentication flow,
authenticated API client, and the first real API-backed domain page.

------------------------------------------------------------------------

# 2. Technology Stack

## Backend

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

## Frontend

``` text
Vue 3
TypeScript
Inertia.js 3
Tailwind CSS 4
Vite 8
Laravel Wayfinder
vue-tsc
ESLint
Prettier
```

------------------------------------------------------------------------

# 3. High-Level System Architecture

``` text
Browser / Mobile Client
        |
        v
FleetTrack
        |
        +-------------------------------+
        |                               |
        v                               v
Laravel / Inertia / API           Traccar Server
        |                               |
        v                               +--> GPS positions
Vue Web Application                    +--> GPS trip detection
        |                               +--> Traccar Devices
        v                               +--> Traccar Geofences
FleetTrack Database                    +--> Device/Geofence permissions
        |                               +--> Tracking events
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

External Traccar identifiers never replace FleetTrack tenancy or
authorization.

------------------------------------------------------------------------

# 4. Backend Application Layers

The primary synchronous backend flow is:

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

Asynchronous external writes use:

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
VerifyTraccarWebhook
 ↓
TraccarEventController
 ↓
Event DTO / Handler
 ↓
Application Event
 ↓
Queued Listener
 ↓
Alert Action
 ↓
Alert Rule resolution
 ↓
Alert persistence
```

Tracking and Reports reads remain synchronous because callers need the
current Traccar result.

------------------------------------------------------------------------

# 5. Controllers, Requests, Actions, Services, Resources

## Controllers

Controllers remain intentionally thin.

Responsibilities:

-   authorize
-   obtain the authenticated user
-   consume validated input
-   parse values where needed
-   invoke Actions
-   return Resources/responses

Controllers should not contain raw Traccar HTTP logic, tenant-transfer
rules, substantial business logic, GPS trip detection, or Alert Rule
evaluation.

Current controller areas include:

``` text
Auth
Company
Fleet
Driver
Vehicle
Device
Geofence
GeofenceVehicle
Tracking
TraccarEvent
Alert
AlertRule
Report
Dashboard
```

## Form Requests

Form Requests own HTTP validation.

Current validation areas include:

``` text
Companies
Fleets
Drivers
Vehicles
Devices
Geofences
Alert Rules
Tracking ranges
Report ranges
```

## Actions

Actions own application/business operations.

Current Action areas include:

``` text
Alert
AlertRule
Company
Dashboard
Device
Driver
Fleet
Geofence
Tracking
Vehicle
```

Reports intentionally reuse Tracking Actions rather than duplicating
report-domain logic.

## Services

External Traccar communication is isolated behind integration services
such as:

``` text
TraccarClient
TraccarDeviceService
TraccarGeofenceService
PositionService
ReportService
```

## API Resources

Resources define stable FleetTrack response contracts and normalize
model/external data before it reaches clients.

Tracking and Reports reuse common Resources where the same contract
applies.

------------------------------------------------------------------------

# 6. Authentication Architecture

Backend authentication uses Laravel Sanctum.

Current API endpoints:

``` text
POST /api/v1/auth/login
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

The current Vue web client uses the existing Sanctum
personal-access-token flow rather than Laravel web-session
authentication.

Frontend authentication architecture:

``` text
Auth/Login.vue
 ↓
authService.login()
 ↓
POST /api/v1/auth/login
 ↓
Sanctum bearer token
 ↓
authToken.ts
 ↓
browser localStorage
```

The token key is:

``` text
fleettrack_auth_token
```

Authenticated API flow:

``` text
Vue feature service
 ↓
apiRequest()
 ↓
Authorization: Bearer <token>
 ↓
Laravel auth:sanctum
 ↓
API controller
```

Authentication restoration:

``` text
AppLayout mounts
 ↓
authState.initialize()
 ↓
stored token exists?
 ↓
GET /api/v1/auth/me
 ↓
authenticated User restored
```

If authentication cannot be restored:

``` text
token removed
 ↓
auth state cleared
 ↓
redirect to /login
```

Logout:

``` text
AppSidebar
 ↓
authState.logout()
 ↓
POST /api/v1/auth/logout
 ↓
remove local token
 ↓
clear User state
 ↓
redirect /login
```

A `401` returned through the shared API client also removes the stored
token.

Important current boundary: the Inertia web routes are not protected by
Laravel's session-based `auth` middleware. The application shell
performs the frontend guard, while all protected data remains
server-side protected through Sanctum and authorization.

Changing this to session-based SPA authentication would be an
architectural change and should be done intentionally rather than
layered on top of the current bearer-token design.

------------------------------------------------------------------------

# 7. Authorization and Multi-Tenancy

FleetTrack uses company-based tenancy.

Authenticated tenant-aware API routes use:

``` text
auth:sanctum
SetPermissionTeam
```

Spatie Laravel Permission is configured with Teams.

Authorization uses:

1.  model Policies
2.  capability/permission gates

Representative permissions include:

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

Models belonging to Companies use shared visibility patterns, including
the `BelongsToCompany` concern.

Core rule:

``` text
Company user
 ↓
only Company-visible FleetTrack entities
 ↓
only then external Traccar data
```

Super Administrator behavior follows the explicit policy/visibility
behavior already implemented.

External IDs never bypass tenant visibility.

------------------------------------------------------------------------

# 8. Core Domain Model

The current domain is approximately:

``` text
Company
├── Users
├── Fleets
├── Drivers
├── Vehicles
│   ├── Device
│   ├── Alerts
│   ├── Alert Rules (optional Vehicle scope)
│   └── Geofences (many-to-many)
├── Devices
├── Geofences
├── Alerts
└── Alert Rules
```

A Vehicle belongs to a Company and may belong to a Fleet.

A Device belongs to a Company and is associated with a Vehicle according
to the existing Device rules.

A Geofence belongs to a Company.

Geofences and Vehicles have a many-to-many FleetTrack relationship.

Alerts are Company-scoped business entities.

Alert Rules belong to a Company and may optionally target a Vehicle.

------------------------------------------------------------------------

# 9. Traccar Boundary

Traccar payloads and HTTP behavior are kept outside controllers and
domain models.

FleetTrack uses dedicated services and DTOs to isolate the external
contract.

Representative DTOs include:

``` text
DeviceData
GeofenceData
GeofenceEventData
OverspeedEventData
IgnitionEventData
DeviceOfflineEventData
```

Architectural rule:

``` text
FleetTrack domain
 ↓
Service / DTO boundary
 ↓
Traccar
```

Do not expose raw Traccar behavior directly in Vue pages or Laravel
controllers.

------------------------------------------------------------------------

# 10. Device Synchronization

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
-   persist synchronization state/timestamp
-   retry failed queue work
-   reconcile existing Geofence associations after synchronization

FleetTrack local writes therefore do not require immediate Traccar
availability.

------------------------------------------------------------------------

# 11. Geofence Architecture

The Geofence module owns:

1.  local CRUD and authorization
2.  Traccar lifecycle synchronization
3.  Geofence ↔ Vehicle associations
4.  Traccar permission synchronization
5.  supported geofence transition events

FleetTrack intentionally models:

``` text
Geofence ↔ Vehicle
```

rather than exposing Traccar's Device relationship as the CRM domain.

The local association is stored in the `geofence_vehicle` pivot.

Synchronization resolves:

``` text
Geofence.traccar_geofence_id
Vehicle -> Device -> traccar_device_id
```

and synchronizes the resulting Traccar permission.

Attach and detach behavior is idempotent.

------------------------------------------------------------------------

# 12. Eventual Consistency and Stale Job Protection

Device and Geofence synchronization is asynchronous, so external IDs may
become available after local relationships are created.

Both sides participate in reconciliation:

``` text
Geofence sync completion
 ↓
reconcile associated Vehicles

Device sync completion
 ↓
reconcile associated Geofences
```

Queued permission jobs protect against stale desired state.

Before a queued attach writes to Traccar, it verifies the local
relationship still exists.

Before a queued detach removes a Traccar permission, it verifies the
relationship has not been recreated.

FleetTrack's local relationship remains the desired-state authority.

------------------------------------------------------------------------

# 13. Traccar Event and Alert Architecture

Supported Traccar events enter through:

``` text
POST /api/v1/traccar/events
```

protected by:

``` text
VerifyTraccarWebhook
```

Current supported event types include:

``` text
geofenceEnter
geofenceExit
deviceOverspeed
ignitionOn
ignitionOff
deviceOffline
```

Typical flow:

``` text
Traccar
 ↓
TraccarEventController
 ↓
event DTO / handler
 ↓
FleetTrack application event
 ↓
queued listener
 ↓
Alert Action
 ↓
ResolveAlertRule
 ↓
Alert persistence
```

Alerts are persistent FleetTrack business entities.

Current Alert API:

``` text
GET   /api/v1/alerts
GET   /api/v1/alerts/{alert}
PATCH /api/v1/alerts/{alert}/acknowledge
```

Alert acknowledgement is idempotent.

Where available, external event IDs participate in duplicate-prevention
behavior.

------------------------------------------------------------------------

# 14. Custom Alert Rules

Alert Rules contain:

``` text
company_id
vehicle_id nullable
name
type
severity
conditions JSON
is_active
```

`vehicle_id = null` means Company-wide.

A Vehicle value means Vehicle-specific.

Supported rule types currently align with supported Alert event sources:

``` text
overspeed
geofence_enter
geofence_exit
ignition_on
ignition_off
device_offline
```

Runtime evaluation is centralized in `ResolveAlertRule`.

Vehicle-specific rules take precedence where applicable, with
Company-wide fallback.

For overspeed rules, `conditions.speed_limit_kmh` is compared against
actual speed in km/h using a strict greater-than comparison.

Custom rules augment the default Alert pipeline. If no custom rule
matches, default Alert behavior remains.

------------------------------------------------------------------------

# 15. Tracking Architecture

Tracking reads are synchronous:

``` text
Tracking API
 ↓
LiveTrackingController
 ↓
Tracking Action
 ↓
PositionService / ReportService
 ↓
Traccar
 ↓
FleetTrack Resource
 ↓
JSON
```

Current tracking endpoints include:

``` text
GET /api/v1/tracking/positions
GET /api/v1/tracking/vehicles/{vehicle}
GET /api/v1/tracking/vehicles/{vehicle}/positions
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

FleetTrack resolves visible local entities and synchronized Devices
before requesting external tracking data.

Online/offline status is derived from GPS-fix freshness through shared
FleetTrack semantics.

Position-history requests use a bounded date range.

FleetTrack delegates detected trip history to Traccar `/reports/trips`;
it does not maintain a competing trip-detection algorithm.

------------------------------------------------------------------------

# 16. Reports Architecture

Reports is a report-specific HTTP layer over existing FleetTrack
tracking/application infrastructure.

``` text
Report HTTP API
 ↓
ReportController
 ↓
reports.view
 ↓
existing Tracking Actions
 ↓
ReportService
 ↓
Traccar report endpoints
 ↓
FleetTrack Resource / response
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

Current Actions include:

``` text
GetVehicleTrips
GetVehicleTripSummary
GetVehicleStops
GetVehicleEvents
GetVehicleRoute
GetVehicleSummary
GetVehicleHours
GetVehicleCombinedReport
```

The Reports controller does not call Traccar directly.

`reports.export` exists as a reserved capability, but the export
format/content/download contract has not been defined. Do not invent it.

------------------------------------------------------------------------

# 17. Dashboard Backend Architecture

Backend endpoint:

``` text
GET /api/v1/dashboard/overview
```

Conceptual flow:

``` text
DashboardController
 ↓
GetDashboardOverview
 ├─ Company/Fleet/Vehicle/Device counts
 ├─ visible Alerts
 └─ live position/connectivity behavior
      ↓
   VehicleOnlineStatus
 ↓
DashboardOverviewResource
```

Current metrics:

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

The Dashboard does not define a second connectivity model.

Online/offline semantics reuse `VehicleOnlineStatus`.

Unsynchronized Devices are not treated as offline synchronized tracking
Devices.

Alert aggregation reuses tenant visibility.

Time-based distance, duration, speed, utilization, or similar KPIs
require an explicit period and aggregation contract before being added.

------------------------------------------------------------------------

# 18. Frontend Application Architecture

The current Vue application has an established reusable architecture.

``` text
Inertia web route
 ↓
Vue Page
 ↓
AppLayout
 ↓
App components + reusable UI components
 ↓
feature service
 ↓
apiRequest()
 ↓
generated Wayfinder API route
 ↓
Laravel API
```

Current active web pages:

``` text
/        -> Dashboard.vue
/login   -> Auth/Login.vue
/fleets  -> Fleets/Index.vue
```

`Welcome.vue` remains in the source tree but is not part of the current
authenticated application flow.

------------------------------------------------------------------------

# 19. Frontend Application Shell

Core application components:

``` text
resources/js/components/app/AppLogo.vue
resources/js/components/app/AppHeader.vue
resources/js/components/app/AppSidebar.vue
resources/js/components/app/AppFooter.vue
resources/js/layouts/AppLayout.vue
```

`AppLayout` owns the shared authenticated page shell.

Current responsive behavior includes:

-   fixed desktop sidebar
-   mobile navigation drawer
-   backdrop
-   Escape-key close behavior
-   body scroll locking while mobile navigation is open
-   responsive content padding
-   integrated footer
-   authentication initialization before protected content is displayed
-   unauthenticated redirect to `/login`

`AppSidebar` owns navigation and authenticated-user presentation.

It reads the User from `authState`, derives initials and role
presentation, and owns Sign out behavior.

------------------------------------------------------------------------

# 20. Reusable Frontend UI Layer

Current reusable UI components:

``` text
AppButton.vue
AppCard.vue
AppCheckbox.vue
AppIcon.vue
AppInput.vue
AppPagination.vue
AppSelect.vue
AppTable.vue
AppTextarea.vue
EmptyState.vue
ErrorState.vue
FormField.vue
LoadingState.vue
PageHeader.vue
StatusBadge.vue
```

Page implementations should reuse these components before introducing
feature-specific duplicates.

Dashboard-specific reusable components currently include:

``` text
DashboardMetricCard.vue
DeviceConnectivity.vue
FleetStatusTable.vue
RecentAlerts.vue
```

------------------------------------------------------------------------

# 21. Frontend Design System

Application styling is centralized in:

``` text
resources/css/app.css
```

The frontend uses semantic application tokens rather than feature pages
directly choosing arbitrary Tailwind palette colors.

Current semantic concepts include:

``` text
brand
brand-hover
brand-dark
brand-soft

app
surface
surface-muted
sidebar

content
content-secondary
muted
subtle

border-default
border-strong

success
warning
danger
info
```

A previous frontend audit found no direct application use of the checked
amber/slate/emerald/orange/red/blue palette utility classes under
`resources/js`.

This semantic-token approach should continue as additional pages are
implemented.

------------------------------------------------------------------------

# 22. Wayfinder and Routing

Laravel Wayfinder generates typed frontend route helpers.

Generated route/action code lives under:

``` text
resources/js/routes/
resources/js/actions/
resources/js/wayfinder/
```

Current generated web helpers include:

``` text
resources/js/routes/web/index.ts
resources/js/routes/web/fleets/index.ts
```

When Laravel routes change:

``` bash
sail artisan wayfinder:generate
```

Generated Wayfinder files should not be manually edited.

Feature services and navigation should use generated helpers where
appropriate.

------------------------------------------------------------------------

# 23. Frontend API Boundary

Authenticated API behavior is centralized in:

``` text
resources/js/services/apiClient.ts
```

`apiRequest<T>()` currently handles:

-   `Accept: application/json`
-   bearer-token attachment
-   centralized API errors
-   token removal on `401`
-   `ApiError`
-   `204 No Content`

Feature services should remain thin and use this shared boundary.

Current services:

``` text
apiClient.ts
authService.ts
authState.ts
authToken.ts
fleetService.ts
```

Current frontend domain types include:

``` text
types/auth.ts
types/fleet.ts
```

Do not duplicate authorization headers or generic fetch/error handling
in individual feature pages.

------------------------------------------------------------------------

# 24. Current Dashboard Frontend

The Dashboard page exists and uses the shared application shell and
reusable Dashboard components.

Important distinction:

``` text
Dashboard backend API      implemented
Dashboard Vue live wiring  not yet implemented
```

The current Vue Dashboard still uses static/mock display data.

Therefore the Dashboard frontend must not yet be described as live-data
complete.

When wired later, it should use the shared API client and the existing
backend `/api/v1/dashboard/overview` contract rather than creating a
second data path.

------------------------------------------------------------------------

# 25. Current Fleets Frontend

The Fleets page is the first domain page connected to real authenticated
API data.

Current files:

``` text
resources/js/pages/Fleets/Index.vue
resources/js/services/fleetService.ts
resources/js/types/fleet.ts
```

Current flow:

``` text
/fleets
 ↓
Fleets/Index.vue
 ↓
getFleets(page)
 ↓
apiRequest()
 ↓
Wayfinder fleets.index URL
 ↓
GET /api/v1/fleets?page=...
 ↓
FleetResource collection
 ↓
Vue table
```

Implemented frontend behavior:

-   authenticated Fleet list
-   loading state
-   error state
-   empty state
-   Fleet table
-   semantic status badge
-   pagination metadata display
-   refresh action
-   responsive layout

Manual browser verification confirmed seeded Fleets load successfully
after authentication.

Not yet implemented in the Vue frontend:

``` text
Create Fleet
Edit Fleet
Delete Fleet
Fleet detail page
fully interactive API pagination
Fleet create/update validation UX
```

The backend Fleet CRUD API already exists, so the next slice should
extend the existing frontend rather than redesigning the backend.

------------------------------------------------------------------------

# 26. Remaining Frontend Modules

Sidebar navigation already represents the broader application
information architecture:

``` text
Dashboard
Fleets
Vehicles
Drivers
Devices
Live Tracking
Geofences
Alerts
Reports
```

Only Dashboard and Fleets currently have active application pages in
that navigation.

The remaining entries are intentionally unavailable/disabled until their
frontend routes/pages are implemented.

Backend APIs for these domains largely already exist.

The established frontend architecture should be applied to each module
rather than creating independent page architectures.

------------------------------------------------------------------------

# 27. Database Architecture and Seed State

Current migrations cover the active FleetTrack domain, including:

``` text
Companies
Users
Permissions
Sanctum personal access tokens
Fleets
Vehicles
Devices
Geofences
Geofence ↔ Vehicle
Alerts
Alert Rules
Drivers
```

Factories exist for the primary current domain models.

Current seeders include:

``` text
CompanyRoleSeeder
CompanySeeder
DatabaseSeeder
DeviceSeeder
DriverSeeder
FleetSeeder
PermissionSeeder
RoleSeeder
TestingRoleSeeder
UserSeeder
VehicleSeeder
```

A current `migrate:fresh --seed` was manually verified at this
checkpoint.

Observed seeded counts:

``` text
4 Companies
4 Fleets
22 Users
```

The seeded System Administrator account exists, is active, and the
factory-generated development password was verified with Laravel
`Hash::check`.

------------------------------------------------------------------------

# 28. Error and Failure Boundaries

FleetTrack separates local business state from external tracking state.

## Local writes

Local changes may succeed before Traccar synchronization completes.

Queue jobs provide eventual synchronization.

## External reads

Tracking and Reports are synchronous, so Traccar read failures may
affect the current API request.

These failures remain behind Traccar services.

## Authentication failures

The frontend API client handles `401` centrally by removing the invalid
local bearer token.

The application shell then prevents unauthenticated protected content
from being used and redirects to Login.

## Webhook failures

Traccar webhook requests must pass webhook authentication and payload
validation before application behavior is triggered.

## Tenant failures

FleetTrack visibility and authorization are enforced before external
tracking data is exposed.

------------------------------------------------------------------------

# 29. Testing Architecture

FleetTrack uses Pest for backend behavior.

Coverage areas include:

``` text
API endpoints
Actions
Policies
Form Requests
Models / relationships
Traccar services
Queue Jobs
Listeners
Traccar event handling
Alert generation
Alert Rule resolution
Tracking
Reports
Dashboard
```

External Traccar HTTP behavior is faked in tests.

Frontend correctness is currently enforced primarily through
static/build quality gates:

``` text
Prettier
ESLint
vue-tsc
Vite build
```

As the frontend grows, behavioral frontend testing can be added
deliberately if/when the project establishes a test framework for it. Do
not invent a second testing stack without a project decision.

------------------------------------------------------------------------

# 30. Quality Gates

## Backend

At a meaningful backend/full-stack commit boundary:

``` bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

## Frontend

Frontend quality gate:

``` bash
npm run format
npm run format:check
npm run lint:check
npm run types:check
npm run build
```

`npm run format` modifies files.

The other frontend commands validate formatting, linting, TypeScript/Vue
types, and production build output.

The project also exposes a combined Composer CI script:

``` bash
sail composer ci:check
```

Use targeted checks during implementation, then run the complete
relevant gate at a meaningful commit boundary.

------------------------------------------------------------------------

# 31. Current Architectural Checkpoint

Backend architecture is substantially established through Reports and
Dashboard.

``` text
Authentication / Authorization / Multi-Tenancy
 ↓
Companies / Fleets / Drivers / Vehicles / Devices
 ↓
Traccar Device synchronization
 ↓
Tracking / Position History / Trip behavior
 ↓
Geofences / Traccar Geofence synchronization
 ↓
Geofence ↔ Vehicle permission synchronization
 ↓
Secure Traccar event ingestion
 ↓
Alerts / acknowledgement / Custom Alert Rules
 ↓
Reports read API
 ↓
Dashboard Overview API
```

Frontend foundation is now established:

``` text
Vue/Inertia application
 ↓
semantic design system
 ↓
reusable UI components
 ↓
responsive AppLayout
 ↓
Wayfinder web routing
 ↓
Login
 ↓
bearer-token authentication
 ↓
auth restoration / guard
 ↓
shared authenticated API client
 ↓
real authenticated sidebar User
 ↓
Logout
 ↓
Fleets list connected to real API
```

The next concrete implementation slice is:

``` text
Fleet frontend CRUD
```

Recommended sequence:

``` text
Create Fleet
 ↓
Edit Fleet
 ↓
Delete Fleet
 ↓
finish pagination/interactions
 ↓
full Fleet page UX validation
 ↓
quality gate
 ↓
commit
```

------------------------------------------------------------------------

# 32. Known Architectural Gaps

These are intentionally not treated as completed:

1.  Dashboard Vue still uses static/mock data.
2.  Fleet frontend is list/read only at the current checkpoint.
3.  Vehicles, Drivers, Devices, Live Tracking, Geofences, Alerts, and
    Reports frontend pages are not yet implemented.
4.  Reports export contract remains undefined.
5.  Additional time-based Dashboard KPI semantics remain undefined.
6.  External Alert delivery such as email/SMS/push is not a defined
    current requirement.
7.  FleetTrack does not currently require its own persistent Trip entity
    because Traccar remains the GPS trip-detection authority.
8.  Current web-route guarding is frontend-based around bearer-token
    auth rather than Laravel session middleware.

------------------------------------------------------------------------

# 33. Architectural Principles

Continue to follow these rules:

1.  FleetTrack owns business-domain state and authorization.
2.  Traccar owns GPS tracking data and GPS trip detection.
3.  Controllers stay thin.
4.  Validation belongs in Form Requests.
5.  Application/business logic belongs in Actions.
6.  External Traccar communication belongs in Services.
7.  Traccar payload translation belongs in DTOs.
8.  Appropriate external writes use Events, Listeners, and Queue Jobs.
9.  Tracking and Reports reads remain synchronous where the caller
    requires the result.
10. Company tenancy is enforced before external data is exposed.
11. Local Geofence ↔ Vehicle state is the desired-state authority for
    Traccar permission synchronization.
12. Queue jobs protect against stale desired state.
13. External-ID dependencies are reconciled after asynchronous
    synchronization.
14. Traccar webhooks are authenticated and translated before application
    behavior.
15. Custom Alert Rules augment default Alert behavior.
16. Reports reuse tracking/application infrastructure.
17. Dashboard reuses established visibility and connectivity semantics.
18. Time-based Dashboard KPIs require an explicit reporting period and
    aggregation contract.
19. Vue pages reuse the shared application shell and UI components.
20. Authenticated frontend API calls use `apiRequest()`.
21. Authentication state is centralized in `authState`.
22. Feature services remain thin.
23. Application colors use semantic tokens from `app.css`.
24. New frontend pages remain responsive.
25. Laravel route changes are followed by Wayfinder regeneration.
26. Generated Wayfinder code is not manually edited.
27. Backend-complete and frontend-complete functionality are documented
    separately.
28. New frontend modules should extend the established architecture
    rather than introduce parallel infrastructure.
