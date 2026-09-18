# FleetTrack Features

## Status

This document records the implemented FleetTrack capabilities and the
remaining product/frontend work at the `FleetTrack_Laravel(7).zip`
checkpoint.

Status is separated between backend/API implementation and frontend/UI
implementation so that an available API is not mistaken for a completed
user-facing feature.

------------------------------------------------------------------------

# Authentication and Access

## Backend --- Implemented

-   Laravel Sanctum authentication
-   Login endpoint
-   Current authenticated-user endpoint
-   Logout endpoint
-   Personal access tokens
-   Company-based tenancy
-   Role and permission authorization
-   Spatie Laravel Permission with Teams
-   Model Policies and capability checks
-   Active seeded development users

API:

``` text
POST /api/v1/auth/login
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

## Frontend --- Implemented

-   Sign-in page
-   Bearer-token storage
-   Shared authenticated API client
-   Authentication restoration after refresh
-   Invalid/expired token handling
-   Protected application shell
-   Redirect to `/login` when unauthenticated
-   Real authenticated user displayed in sidebar
-   User initials
-   User role display
-   Sign out
-   Redirect to Login after sign out
-   Authentication works across Dashboard and Fleets

Current development credentials after a fresh seeded database:

``` text
admin@fleettrack.test
password
```

These are development seed credentials, not production credentials.

------------------------------------------------------------------------

# Application Shell and Design System

## Frontend --- Implemented

-   Vue 3 + TypeScript application
-   Inertia navigation
-   Tailwind CSS styling
-   Laravel Wayfinder typed routes
-   Responsive application layout
-   Desktop sidebar
-   Mobile navigation drawer
-   Mobile backdrop
-   Escape-key drawer close
-   Body scroll locking while mobile navigation is open
-   Header
-   Footer
-   FleetTrack branding/logo
-   Semantic application colors
-   Reusable loading, error, and empty states
-   Reusable forms and table components

Current reusable UI components:

``` text
AppButton
AppCard
AppCheckbox
AppIcon
AppInput
AppPagination
AppSelect
AppTable
AppTextarea
EmptyState
ErrorState
FormField
LoadingState
PageHeader
StatusBadge
```

Current application components:

``` text
AppLogo
AppHeader
AppSidebar
AppFooter
AppLayout
```

------------------------------------------------------------------------

# Companies

## Backend --- Implemented

-   Company model
-   Company CRUD API
-   Validation
-   Authorization
-   API Resources
-   Factory and seeding support
-   Automated tests
-   Company relationships to tenant-owned domain data

## Frontend --- Not Yet Implemented

There is no current user-facing Company management page in the Vue
application.

Company frontend requirements should be defined before adding
navigation/UI.

------------------------------------------------------------------------

# Fleets

## Backend --- Implemented

-   Fleet model
-   Company ownership
-   Fleet CRUD API
-   Create Action
-   Update Action
-   Delete Action
-   Store/Update validation
-   Policy authorization
-   Tenant visibility
-   API Resource
-   Factory and seeding support
-   Automated tests

API:

``` text
GET    /api/v1/fleets
POST   /api/v1/fleets
GET    /api/v1/fleets/{fleet}
PUT    /api/v1/fleets/{fleet}
PATCH  /api/v1/fleets/{fleet}
DELETE /api/v1/fleets/{fleet}
```

Fleet fields currently include:

``` text
company_id
name
code
email
phone
address
latitude
longitude
timezone
description
is_active
```

## Frontend --- Partially Implemented

Implemented:

-   `/fleets` Inertia route
-   Fleets page
-   real authenticated Fleet API loading
-   typed Fleet response
-   Fleet service
-   loading state
-   API error state
-   empty state
-   Fleet table
-   code display
-   contact display
-   timezone display
-   active/inactive status badge
-   pagination metadata
-   refresh action
-   responsive application shell

Verified with seeded Fleet records in the browser.

Remaining:

-   Create Fleet UI
-   Edit Fleet UI
-   Delete Fleet UI
-   Fleet detail UI if required
-   create/update form validation UX
-   confirmation UX for destructive actions
-   fully interactive pagination controls
-   final Fleet CRUD browser verification

This is the current next development slice.

------------------------------------------------------------------------

# Drivers

## Backend --- Implemented

-   Driver model
-   Company ownership
-   Driver CRUD API
-   Actions
-   validation
-   authorization
-   API Resource
-   factory and seeding support
-   automated tests

API:

``` text
GET    /api/v1/drivers
POST   /api/v1/drivers
GET    /api/v1/drivers/{driver}
PUT    /api/v1/drivers/{driver}
PATCH  /api/v1/drivers/{driver}
DELETE /api/v1/drivers/{driver}
```

## Frontend --- Not Yet Implemented

The sidebar contains Drivers as part of the planned information
architecture, but the item is currently unavailable/disabled.

------------------------------------------------------------------------

# Vehicles

## Backend --- Implemented

-   Vehicle model
-   Company ownership
-   Fleet relationship
-   Vehicle CRUD API
-   Actions
-   validation
-   authorization
-   tenant visibility
-   API Resource
-   factory and seeding support
-   automated tests
-   Device relationship
-   Geofence associations

API:

``` text
GET    /api/v1/vehicles
POST   /api/v1/vehicles
GET    /api/v1/vehicles/{vehicle}
PUT    /api/v1/vehicles/{vehicle}
PATCH  /api/v1/vehicles/{vehicle}
DELETE /api/v1/vehicles/{vehicle}
```

## Frontend --- Not Yet Implemented

The Vehicles navigation item is present but unavailable/disabled.

------------------------------------------------------------------------

# Devices

## Backend --- Implemented

-   Device model
-   Company ownership
-   Vehicle association
-   Device CRUD API
-   validation
-   authorization
-   API Resource
-   Traccar Device ID
-   Device status
-   synchronization timestamp/state
-   asynchronous Traccar lifecycle synchronization
-   create/update/delete synchronization
-   queue retry behavior
-   reconciliation of Geofence permissions after Device synchronization
-   factory and seeding support
-   automated tests

API:

``` text
GET    /api/v1/devices
POST   /api/v1/devices
GET    /api/v1/devices/{device}
PUT    /api/v1/devices/{device}
PATCH  /api/v1/devices/{device}
DELETE /api/v1/devices/{device}
```

## Frontend --- Not Yet Implemented

The Devices navigation item is present but unavailable/disabled.

------------------------------------------------------------------------

# Live Tracking

## Backend --- Implemented

Current API:

``` text
GET /api/v1/tracking/positions
GET /api/v1/tracking/vehicles/{vehicle}
GET /api/v1/tracking/vehicles/{vehicle}/positions
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

Implemented behavior includes:

-   live position retrieval
-   Fleet filtering
-   Vehicle filtering
-   tenant-safe Vehicle visibility
-   position history
-   bounded date-range validation
-   trip retrieval through Traccar
-   trip summary
-   synchronized Device resolution
-   online/offline Vehicle status
-   last-seen behavior
-   shared GPS-fix freshness semantics
-   automated tests

FleetTrack does not duplicate Traccar GPS trip detection.

## Frontend --- Not Yet Implemented

The Live Tracking navigation item is present but unavailable/disabled.

The future page should consume the existing tracking API rather than
query Traccar directly.

------------------------------------------------------------------------

# Geofences

## Backend --- Implemented

-   Geofence model
-   Company ownership
-   CRUD API
-   validation
-   authorization
-   API Resource
-   Traccar Geofence synchronization
-   asynchronous create/update/delete synchronization
-   Geofence ↔ Vehicle many-to-many relationship
-   attach Vehicle API
-   detach Vehicle API
-   Traccar Device/Geofence permission synchronization
-   reconciliation when external IDs become available later
-   stale queued-job protection
-   idempotent association behavior
-   automated tests

API includes:

``` text
GET    /api/v1/geofences
POST   /api/v1/geofences
GET    /api/v1/geofences/{geofence}
PUT    /api/v1/geofences/{geofence}
PATCH  /api/v1/geofences/{geofence}
DELETE /api/v1/geofences/{geofence}

POST   /api/v1/geofences/{geofence}/vehicles/{vehicle}
DELETE /api/v1/geofences/{geofence}/vehicles/{vehicle}
```

## Frontend --- Not Yet Implemented

The Geofences navigation item is present but unavailable/disabled.

------------------------------------------------------------------------

# Alerts

## Backend --- Implemented

-   persistent Alert model
-   Company ownership
-   tenant visibility
-   Alert listing
-   Alert detail
-   Alert acknowledgement
-   idempotent acknowledgement
-   Device offline Alerts
-   Geofence enter Alerts
-   Geofence exit Alerts
-   Ignition-on Alerts
-   Ignition-off Alerts
-   Overspeed Alerts
-   Traccar event translation
-   queued Alert handling
-   duplicate-prevention support where external event IDs are available
-   automated tests

API:

``` text
GET   /api/v1/alerts
GET   /api/v1/alerts/{alert}
PATCH /api/v1/alerts/{alert}/acknowledge
```

## Frontend --- Not Yet Implemented

The Alerts navigation item is present but unavailable/disabled.

External Alert delivery such as email, SMS, or push is not currently a
defined project requirement.

------------------------------------------------------------------------

# Custom Alert Rules

## Backend --- Implemented

-   Alert Rule model
-   Company ownership
-   optional Vehicle scope
-   CRUD API
-   validation
-   authorization
-   tenant visibility
-   active/inactive rules
-   severity
-   JSON conditions
-   runtime rule resolution
-   Vehicle-specific precedence
-   Company-wide fallback
-   overspeed threshold evaluation
-   integration with supported incoming events
-   automated tests

Supported rule types:

``` text
overspeed
geofence_enter
geofence_exit
ignition_on
ignition_off
device_offline
```

API:

``` text
GET    /api/v1/alert-rules
POST   /api/v1/alert-rules
GET    /api/v1/alert-rules/{alertRule}
PUT    /api/v1/alert-rules/{alertRule}
PATCH  /api/v1/alert-rules/{alertRule}
DELETE /api/v1/alert-rules/{alertRule}
```

## Frontend --- Not Yet Implemented

No Alert Rule management page is currently active in the Vue
application.

------------------------------------------------------------------------

# Traccar Event Ingestion

## Backend --- Implemented

Webhook endpoint:

``` text
POST /api/v1/traccar/events
```

Protected by:

``` text
VerifyTraccarWebhook
```

Supported event families include:

``` text
deviceOffline
geofenceEnter
geofenceExit
ignitionOn
ignitionOff
deviceOverspeed
```

Events are translated into FleetTrack application behavior before Alerts
are persisted.

------------------------------------------------------------------------

# Reports

## Backend --- Implemented

Current Vehicle report API:

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

Implemented behavior includes:

-   tenant-safe Vehicle access
-   `reports.view` authorization
-   date-range validation
-   Trips
-   Trip Summary
-   Stops
-   Events
-   Route
-   Summary
-   Hours
-   Combined report
-   reuse of Tracking Actions
-   reuse of Traccar ReportService
-   FleetTrack Resources
-   automated tests

## Frontend --- Not Yet Implemented

The Reports navigation item is present but unavailable/disabled.

## Intentionally Undefined

`reports.export` exists as a capability, but the product contract for
export format, content, and delivery has not been defined.

Do not implement an export format based on assumptions.

------------------------------------------------------------------------

# Dashboard

## Backend --- Implemented

API:

``` text
GET /api/v1/dashboard/overview
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

Implemented behavior includes:

-   Company-scoped overview
-   Super Administrator overview
-   tenant-safe counts
-   live Vehicle connectivity
-   shared `VehicleOnlineStatus`
-   Alert totals
-   unacknowledged Alert totals
-   automated tests

## Frontend --- UI Implemented, Live Data Pending

Current Dashboard components:

``` text
DashboardMetricCard
DeviceConnectivity
FleetStatusTable
RecentAlerts
```

The Dashboard is responsive and integrated into the shared application
shell.

However, its displayed data is currently static/mock.

The Vue Dashboard is not yet wired to:

``` text
GET /api/v1/dashboard/overview
```

Therefore the Dashboard frontend is visually implemented but not yet
live-data complete.

------------------------------------------------------------------------

# Database, Factories, and Seeders

## Implemented

Current database domain includes:

-   Companies
-   Users
-   permissions/roles
-   Sanctum personal access tokens
-   Fleets
-   Drivers
-   Vehicles
-   Devices
-   Geofences
-   Geofence ↔ Vehicle associations
-   Alerts
-   Alert Rules

Factories exist for the primary domain models.

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

The current development database was successfully rebuilt using fresh
migrations and seed data.

Verified seeded counts at the current checkpoint:

``` text
Companies: 4
Fleets:    4
Users:     22
```

------------------------------------------------------------------------

# Responsive Frontend

## Implemented Foundation

The shared frontend is designed for desktop and mobile use.

Implemented responsive behavior includes:

-   desktop sidebar
-   mobile drawer
-   responsive page spacing
-   responsive header behavior
-   responsive Dashboard components
-   responsive Fleet page shell
-   scroll-safe tables
-   reusable responsive UI primitives

Every new page should be tested at both desktop and mobile widths.

Responsive behavior is a feature requirement, not a cleanup task to
postpone until the end.

------------------------------------------------------------------------

# Frontend Quality and Code Standards

Current frontend scripts support:

``` bash
npm run format
npm run format:check
npm run lint:check
npm run types:check
npm run build
```

The authentication/frontend foundation passed the relevant checks at the
current checkpoint.

Application UI should continue using semantic tokens from:

``` text
resources/css/app.css
```

rather than hard-coded feature-specific Tailwind palette colors.

------------------------------------------------------------------------

# Backend Quality and Code Standards

At meaningful backend/full-stack boundaries, run:

``` bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

The project also provides:

``` bash
sail composer ci:check
```

for a combined CI-oriented check.

------------------------------------------------------------------------

# Current Product Status Summary

## Backend/API

Substantially implemented:

``` text
Authentication
Authorization
Multi-tenancy
Companies
Fleets
Drivers
Vehicles
Devices
Traccar Device synchronization
Live Tracking
Trip/Position history
Geofences
Geofence/Vehicle permissions
Traccar event ingestion
Alerts
Custom Alert Rules
Reports
Dashboard Overview
```

## Frontend

Implemented foundation:

``` text
Application shell
Responsive layout
Design tokens
Reusable UI components
Wayfinder navigation
Login
Authentication persistence/restoration
Authenticated API client
Authenticated user sidebar
Logout
Dashboard visual UI
Fleet list with real API data
```

Frontend domain CRUD remains much less complete than the backend.

------------------------------------------------------------------------

# Next Development Work

## Immediate

Complete Fleet frontend CRUD using the already implemented Fleet API:

``` text
1. Create Fleet
2. Edit Fleet
3. Delete Fleet
4. Complete interactive pagination
5. Validate loading/error/empty/form states
6. Validate responsive behavior
7. Run full frontend quality gate
8. Commit the completed Fleet frontend slice
```

## Then

Apply the established frontend architecture to the remaining backend
modules.

Likely progression:

``` text
Vehicles
Drivers
Devices
Live Tracking
Geofences
Alerts
Reports
Dashboard live-data wiring
```

The exact order can follow product priority.

------------------------------------------------------------------------

# Known Remaining / Undefined Work

The following should not be reported as completed:

-   Fleet create/edit/delete frontend
-   Vehicle frontend
-   Driver frontend
-   Device frontend
-   Live Tracking frontend
-   Geofence frontend
-   Alert frontend
-   Alert Rule frontend
-   Reports frontend
-   Dashboard live-data wiring
-   Reports export contract
-   additional time-period Dashboard KPI contract
-   external Alert notification channels

FleetTrack also intentionally does not maintain a competing persistent
GPS Trip detection model at this stage; Traccar remains responsible for
detected trips.

------------------------------------------------------------------------

# Feature Implementation Principle

A backend API being implemented does not mean the corresponding end-user
feature is complete.

For documentation and planning, use these distinctions:

``` text
Backend implemented
Frontend implemented
Fully integrated / user-facing complete
```

The current project has a mature backend foundation and an established
frontend foundation. The next phase is primarily to expose the existing
backend capabilities through consistent, responsive Vue pages using the
architecture already in place.
