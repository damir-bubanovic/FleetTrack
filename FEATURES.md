# FleetTrack Features

## Status

This document records the implemented FleetTrack capabilities and the
remaining product/frontend work at the `FleetTrack_Laravel(5).zip`
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

## Frontend --- Implemented

The Fleets frontend is implemented through the established application shell.

Implemented:

-   `/fleets` Inertia route/page
-   API-backed paginated listing
-   Create Fleet
-   Edit Fleet
-   Delete Fleet
-   interactive pagination
-   loading, error, and empty states
-   status presentation
-   reusable `CreateFleetForm.vue`
-   field-level Laravel `422` validation errors
-   automatic clearing of a field error when the value changes
-   safe generic handling of `5xx` responses
-   responsive Dashboard-consistent layout

Fleet validation now mirrors database constraints: name and code uniqueness are
company-scoped, update rules ignore the current Fleet, code normalization occurs
before validation, and blank/null timezone input falls back to the application
timezone instead of reaching MySQL as an invalid null value.

------------------------------------------------------------------------

# Drivers

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

## Frontend --- Implemented

Implemented:

-   `/vehicles` Inertia route/page
-   API-backed paginated listing
-   Create Vehicle
-   Edit Vehicle
-   Delete Vehicle
-   Fleet selector
-   loading, error, and empty states
-   status presentation
-   reusable `VehicleForm.vue`
-   field-level Laravel `422` validation errors
-   automatic clearing of corrected field errors
-   safe generic handling of `5xx` responses
-   responsive Dashboard/Fleets-consistent layout

Vehicle validation is aligned with persistence constraints. Blank/null odometer
input is normalized to `0`, duplicate VIN is rejected by Laravel validation,
and update VIN uniqueness ignores the current Vehicle.

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
Authentication and authorization
Multi-tenancy
Companies
Fleets
Drivers
Vehicles
Devices
Traccar Device synchronization
Live Tracking and position history
Geofences and Vehicle associations
Traccar event ingestion
Alerts and Custom Alert Rules
Reports
Dashboard Overview
```

## Frontend

Implemented:

``` text
Application shell and responsive design system
Reusable UI components
Wayfinder navigation
Login / auth persistence / Logout
Shared API client with field-level 422 errors and protected 5xx handling
Dashboard visual UI
Fleets CRUD
Vehicles CRUD
```

Not yet implemented as user-facing Vue modules:

``` text
Companies administration
Drivers
Devices
Live Tracking
Geofences
Alerts / Alert Rules
Reports
Dashboard live-data wiring
```

# Next Development Work

## Immediate

Implement Drivers frontend CRUD using the existing Driver API and the patterns
established by Fleets and Vehicles:

``` text
1. Add Driver TypeScript types
2. Add thin driverService.ts API wrapper
3. Add Driver create/edit form
4. Add Drivers index/table and web route
5. Add sidebar navigation
6. Add create/edit/delete and pagination
7. Wire Laravel 422 field errors through ApiError/FormField
8. Browser-test responsive/loading/error/empty states
9. Run the complete backend/frontend quality gate
10. Commit the completed Drivers slice
```

## Then

``` text
Devices
Live Tracking
Geofences
Alerts / Alert Rules
Reports
Dashboard live-data wiring
```

# Known Remaining / Undefined Work

The following should not be reported as completed:

-   Company administration frontend
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

FleetTrack intentionally does not maintain a competing persistent GPS Trip
detection model at this stage; Traccar remains responsible for detected trips.

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
