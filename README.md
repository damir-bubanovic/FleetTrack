# FleetTrack

FleetTrack is a multi-tenant fleet-management and GPS-tracking
application built with Laravel, Vue, Inertia, and Traccar.

FleetTrack owns the business domain, tenancy, authorization, alerts,
reporting contracts, and application UI. Traccar provides the external
GPS/tracking engine for positions, detected trips, Devices, Geofences,
permissions, and supported tracking events.

## Current Status

The backend foundation is substantially implemented through:

``` text
Authentication and authorization
Companies
Fleets
Drivers
Vehicles
Devices
Traccar Device synchronization
Live Tracking
Position and trip history
Geofences
Geofence ↔ Vehicle synchronization
Traccar event ingestion
Alerts
Custom Alert Rules
Reports
Dashboard Overview
```

The frontend foundation is also established:

``` text
Vue 3 + TypeScript
Inertia
Tailwind CSS
responsive application shell
semantic design tokens
reusable UI components
Wayfinder routing
Login
bearer-token authentication
auth restoration
shared authenticated API client
authenticated user sidebar
Logout
Dashboard visual UI
real API-backed Fleet listing
```

The current development focus is completing the Fleet frontend CRUD
experience.

------------------------------------------------------------------------

# Technology Stack

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

# Requirements

Local development expects:

-   Docker
-   Laravel Sail
-   Node.js / npm
-   Git

The project is developed through Laravel Sail for PHP/Laravel commands.

------------------------------------------------------------------------

# Installation

Clone the repository and enter the project directory.

Install PHP dependencies if required:

``` bash
composer install
```

Start Sail:

``` bash
./vendor/bin/sail up -d
```

If `sail` is configured as a shell alias, the project commands can use:

``` bash
sail ...
```

Install frontend dependencies:

``` bash
npm install
```

Create the environment file if needed:

``` bash
cp .env.example .env
```

Generate the Laravel application key if needed:

``` bash
sail artisan key:generate
```

Configure the database and other environment settings in `.env`.

Then rebuild the local database with development data:

``` bash
sail artisan migrate:fresh --seed
```

Generate the current Wayfinder route/action files if required:

``` bash
sail artisan wayfinder:generate
```

Start the frontend development server:

``` bash
npm run dev
```

------------------------------------------------------------------------

# Development Login

The current seeded development database provides a System Administrator
account:

``` text
Email:    admin@fleettrack.test
Password: password
```

These credentials are for local seeded development only.

The seed password was verified at the current project checkpoint using
Laravel's `Hash::check`.

Login page:

``` text
/login
```

After authentication, the bearer token is persisted by the frontend and
used for protected API requests.

------------------------------------------------------------------------

# Web Application

Current active Inertia web routes:

``` text
/        Dashboard
/login   Login
/fleets  Fleets
```

The sidebar also represents the planned application areas:

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

At the current checkpoint, the later domain entries are intentionally
unavailable until their Vue pages are implemented.

------------------------------------------------------------------------

# Authentication

FleetTrack uses Laravel Sanctum.

Backend authentication API:

``` text
POST /api/v1/auth/login
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

The current web frontend uses Sanctum personal access tokens.

Frontend flow:

``` text
Login.vue
 ↓
authService
 ↓
Laravel Auth API
 ↓
Sanctum bearer token
 ↓
authToken
 ↓
localStorage
 ↓
apiRequest()
```

The token is stored under:

``` text
fleettrack_auth_token
```

`authState` restores the authenticated user through `/auth/me` when the
application shell initializes.

A `401` response through the shared API client removes an invalid token.

`AppLayout` redirects unauthenticated users to `/login`.

Sign out calls the backend logout endpoint and clears the local
authentication state.

------------------------------------------------------------------------

# Frontend Structure

Important frontend areas:

``` text
resources/
├── css/
│   └── app.css
└── js/
    ├── app.ts
    ├── actions/
    ├── components/
    │   ├── app/
    │   ├── dashboard/
    │   └── ui/
    ├── layouts/
    ├── pages/
    ├── routes/
    ├── services/
    ├── types/
    └── wayfinder/
```

## Application Components

``` text
AppLogo.vue
AppHeader.vue
AppSidebar.vue
AppFooter.vue
AppLayout.vue
```

The application shell supports both desktop and mobile layouts.

## Reusable UI

Current reusable UI components include:

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

New pages should reuse this layer rather than duplicate basic controls.

------------------------------------------------------------------------

# Design System

Application styling and semantic colors are centralized in:

``` text
resources/css/app.css
```

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

Feature pages should use semantic application tokens rather than
hard-coded Tailwind palette colors.

The shared layout and current pages are responsive.

Responsive behavior should remain part of every new page implementation.

------------------------------------------------------------------------

# Frontend API Client

Authenticated API access is centralized in:

``` text
resources/js/services/apiClient.ts
```

Feature services should use the shared `apiRequest()` helper.

It currently handles:

-   JSON API acceptance
-   bearer-token authorization
-   common API errors
-   `401` token cleanup
-   `ApiError`
-   `204 No Content`

Current frontend services include:

``` text
apiClient.ts
authService.ts
authState.ts
authToken.ts
fleetService.ts
```

Current frontend domain types include:

``` text
auth.ts
fleet.ts
```

------------------------------------------------------------------------

# Wayfinder

Laravel Wayfinder generates typed frontend routes/actions.

Generated files live under:

``` text
resources/js/routes/
resources/js/actions/
```

When Laravel routes change, regenerate them:

``` bash
sail artisan wayfinder:generate
```

Do not manually edit generated Wayfinder files.

Current web helpers cover:

``` text
Dashboard
Login
Fleets
```

------------------------------------------------------------------------

# Fleets

The Fleet backend is fully implemented.

API:

``` text
GET    /api/v1/fleets
POST   /api/v1/fleets
GET    /api/v1/fleets/{fleet}
PUT    /api/v1/fleets/{fleet}
PATCH  /api/v1/fleets/{fleet}
DELETE /api/v1/fleets/{fleet}
```

The Vue Fleet page currently implements the authenticated list/read
flow.

Current frontend files:

``` text
resources/js/pages/Fleets/Index.vue
resources/js/services/fleetService.ts
resources/js/types/fleet.ts
```

Implemented frontend behavior:

-   real API loading
-   loading state
-   error state
-   empty state
-   Fleet table
-   Fleet status
-   contact information
-   timezone
-   pagination metadata
-   refresh
-   responsive layout

Remaining Fleet frontend work:

``` text
Create
Edit
Delete
interactive pagination
form validation UX
final responsive/browser verification
```

This is the immediate next development slice.

------------------------------------------------------------------------

# Dashboard

Backend Dashboard API:

``` text
GET /api/v1/dashboard/overview
```

Current backend metrics:

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

The Vue Dashboard UI exists and is responsive.

Current Dashboard components:

``` text
DashboardMetricCard.vue
DeviceConnectivity.vue
FleetStatusTable.vue
RecentAlerts.vue
```

Important: the current Vue Dashboard still uses static/mock display
data.

It is not yet connected to `/api/v1/dashboard/overview`.

------------------------------------------------------------------------

# Tracking

Current tracking API:

``` text
GET /api/v1/tracking/positions
GET /api/v1/tracking/vehicles/{vehicle}
GET /api/v1/tracking/vehicles/{vehicle}/positions
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

FleetTrack delegates GPS position/trip data to Traccar while enforcing
FleetTrack tenant visibility.

The Live Tracking Vue page has not yet been implemented.

------------------------------------------------------------------------

# Reports

Current report API:

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

The Reports Vue page has not yet been implemented.

The `reports.export` permission exists, but the export product contract
remains intentionally undefined.

------------------------------------------------------------------------

# Database and Seed Data

The current database covers:

``` text
Companies
Users
Roles / permissions
Sanctum personal access tokens
Fleets
Drivers
Vehicles
Devices
Geofences
Geofence ↔ Vehicle associations
Alerts
Alert Rules
```

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

At the current checkpoint, a fresh seeded database was manually verified
with:

``` text
Companies: 4
Fleets:    4
Users:     22
```

------------------------------------------------------------------------

# Backend Development Commands

Run Laravel/PHP commands through Sail.

Formatting:

``` bash
sail composer lint
```

Formatting check:

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

At a meaningful backend/full-stack commit boundary, run all four:

``` bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

The project also exposes:

``` bash
sail composer ci:check
```

for the combined CI-oriented checks.

------------------------------------------------------------------------

# Frontend Development Commands

Development server:

``` bash
npm run dev
```

Format:

``` bash
npm run format
```

Check formatting:

``` bash
npm run format:check
```

Lint:

``` bash
npm run lint:check
```

Vue/TypeScript type check:

``` bash
npm run types:check
```

Production build:

``` bash
npm run build
```

At a meaningful frontend commit boundary:

``` bash
npm run format
npm run format:check
npm run lint:check
npm run types:check
npm run build
```

`npm run format` modifies source files; the remaining commands validate
the result.

------------------------------------------------------------------------

# Testing Workflow

During implementation:

1.  make the smallest coherent change
2.  run targeted tests/checks
3.  fix failures immediately
4.  continue the feature
5.  run the complete relevant quality gate when the feature slice is
    complete
6.  inspect `git status`
7.  commit only the intended files

Do not wait until many unrelated modules have changed before running
tests.

------------------------------------------------------------------------

# Project Architecture Rules

Keep the existing backend flow:

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

Keep Traccar communication behind Services.

Keep controllers thin.

Keep HTTP validation in Form Requests.

Keep application behavior in Actions.

Keep tenant visibility enforced before external data is returned.

Keep the existing frontend flow:

``` text
Inertia route
 ↓
Vue page
 ↓
AppLayout / reusable components
 ↓
feature service
 ↓
apiRequest()
 ↓
Laravel API
```

Do not duplicate auth or generic API behavior inside feature pages.

------------------------------------------------------------------------

# Current Development Checkpoint

Completed frontend foundation:

``` text
responsive shell
design system
reusable UI layer
Wayfinder web routes
Login
token authentication
auth restoration
shared API client
protected application shell
authenticated sidebar User
Logout
real Fleet listing
```

The immediate next task is:

``` text
Fleet frontend CRUD
```

Recommended sequence:

``` text
Create Fleet
Edit Fleet
Delete Fleet
Pagination/interactions
Responsive/browser validation
Frontend quality gate
Commit
```

After Fleets, continue exposing the existing backend modules through the
established frontend architecture.

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

------------------------------------------------------------------------

# Documentation

Project documentation:

``` text
README.md
FEATURES.md
ARCHITECTURE.md
AGENTS.md
docs/ARCHITECTURE_DECISIONS.md
```

Responsibilities:

-   `README.md` --- project entry point, setup, commands, and current
    high-level status
-   `FEATURES.md` --- detailed implemented/remaining feature status
-   `ARCHITECTURE.md` --- system architecture and integration flows
-   `AGENTS.md` --- continuation rules and exact development checkpoint
-   `docs/ARCHITECTURE_DECISIONS.md` --- durable architectural decisions
    and rationale

The source code is authoritative if documentation and implementation
diverge.
