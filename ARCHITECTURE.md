# FleetTrack Architecture

This document describes the architecture present in the source snapshot supplied on 2026-09-21. Source code remains authoritative.

## 1. System boundary

```text
Vue 3 / Inertia web UI
        ↓
Laravel API + business domain
        ↓
Traccar services / REST API
```

FleetTrack owns users, companies, tenancy, fleets, vehicles, drivers, local device/geofence records, permissions, alert rules, alerts, report contracts, and the UI. Traccar owns GPS/tracking execution and external device/geofence/trip/position data.

## 2. Technology stack

Backend: PHP 8.3+, Laravel 13, Sanctum, Spatie Permission Teams, MySQL, Redis, Sail, Pest, PHPStan/Larastan, Pint.

Frontend: Vue 3, TypeScript, Inertia 3, Tailwind CSS 4, Vite 8, Wayfinder, vue-tsc, ESLint, Prettier.

## 3. Backend layering

The established request path is:

```text
Route
→ Controller
→ Form Request / authorization
→ Action
→ Service or Eloquent domain operation
→ API Resource
```

Controllers stay thin. HTTP validation belongs in Form Requests. Business operations live in Actions. External Traccar calls are isolated behind services/DTOs. Resources define API output shapes.

## 4. Authentication and authorization

Laravel Sanctum personal access tokens back the current web/API authentication flow. The Vue client persists the bearer token and restores the authenticated user through `/api/v1/auth/me`.

Spatie Laravel Permission uses teams for company-scoped roles/permissions. Tenant visibility is checked before external Traccar reads so an external ID cannot bypass FleetTrack authorization.

## 5. Core domain model

```text
Company
├── Fleets
├── Vehicles
│   └── Device (0..1)
├── Drivers (company_id; assigned to Fleet)
├── Geofences
│   └── Vehicles (many-to-many)
├── AlertRules
└── Alerts

Fleet
├── Vehicles
└── Drivers
```

The system company is used for system administration and is intentionally excluded from operational development seed data.

## 6. Traccar boundary

FleetTrack stores local business entities and nullable Traccar identifiers. Synchronization is explicit rather than treating Traccar as the application database.

### Devices

Local Device lifecycle changes can synchronize to Traccar through the existing event/job/service infrastructure. `traccar_device_id` and `last_sync_at` represent real synchronization state.

### Geofences

FleetTrack models Geofence ↔ Vehicle. Traccar may require device IDs, so synchronization translates FleetTrack vehicle relationships into Traccar device/geofence relationships when external IDs are available.

Queue jobs verify current desired state to avoid stale asynchronous writes reintroducing relationships that have since changed.

### Tracking and reports

Live tracking/report reads remain synchronous because callers need current external results. FleetTrack authorizes the company/vehicle first and then calls the Traccar boundary.

## 7. Traccar events and alerts

Authenticated Traccar webhook events are translated into FleetTrack behavior. Supported paths include overspeed, geofence transitions, ignition changes, and device-offline events.

Alerts are persistent FleetTrack entities and can be acknowledged. Alert rules augment default behavior and can be company-wide or vehicle-specific. Rule resolution prioritizes the matching vehicle-specific rule over a company-wide rule.

## 8. Tracking API architecture

`LiveTrackingController` exposes:

```text
GET tracking/positions
GET tracking/vehicles/{vehicle}
GET tracking/vehicles/{vehicle}/positions
GET tracking/vehicles/{vehicle}/trip-summary
GET tracking/vehicles/{vehicle}/trips
```

The current backend supports filtering, visibility enforcement, online/offline status derived from GPS fix recency, last-seen timestamps, history, and trip data.

The frontend tracking module has not yet been implemented.

## 9. Reports architecture

`ReportController` exposes vehicle trips, trip summary, stops, events, route, summary, hours, and a combined report. Report actions reuse the existing authorization and Traccar report infrastructure rather than creating a parallel reporting data source.

Report export remains undefined pending product requirements.

## 10. Dashboard architecture

`GetDashboardOverview` supplies aggregate counts/status information through `GET /api/v1/dashboard/overview`.

The current Vue Dashboard is a visual implementation only; connecting it to this endpoint remains pending.

## 11. Frontend architecture

The web app uses Vue 3 pages rendered by Inertia and a shared `AppLayout`.

Active pages:

```text
Auth/Login.vue
Dashboard.vue
Fleets/Index.vue
Vehicles/Index.vue
Drivers/Index.vue
Devices/Index.vue
```

Feature modules follow this pattern:

```text
page
→ feature component/form
→ feature service
→ shared apiRequest()
→ Laravel API
```

Current feature services/types exist for fleets, vehicles, drivers, and devices in addition to authentication.

## 12. Shared frontend infrastructure

Application components:

```text
AppLogo
AppHeader
AppSidebar
AppFooter
AppLayout
```

Shared UI includes buttons, cards, inputs, selects, textareas, tables, pagination, status badges, form fields, page headers, loading/error/empty states, and icons.

Semantic design tokens are centralized in `resources/css/app.css`. New pages should reuse these tokens/components.

## 13. Wayfinder

Laravel route/action helpers under `resources/js/routes` and `resources/js/actions` are generated artifacts. Laravel route definitions are authoritative. After route changes:

```bash
sail artisan wayfinder:generate
```

Generated files are not hand-edited.

## 14. Error boundaries

- User-correctable request validation is handled by Laravel Form Requests.
- Frontend `ApiError` preserves `422` field errors for form display.
- Unexpected server details are not surfaced verbatim in normal frontend error messages.
- `401` clears invalid frontend auth state.
- External Traccar failures remain behind the service/action boundary.
- Tenant checks happen before external reads.

## 15. Database and seed architecture

`DatabaseSeeder` establishes permissions/roles/users before operational records, then creates fleets → vehicles/drivers → devices → geofences → alert rules → alerts.

Fresh-seed expected totals:

```text
4 companies
6 fleets
30 vehicles
30 drivers
30 devices
9 geofences
21 alert rules
18 alerts
```

Each customer company has two fleets with five vehicles and five drivers per fleet. Each vehicle gets one local device. Geofences receive overlapping vehicle assignments. Rules and alerts cover representative event types and acknowledgement states.

Fake external Traccar IDs are intentionally not seeded.

## 16. Testing architecture

The project has feature/unit coverage across authentication, CRUD APIs, policies, requests, tracking, Traccar services/jobs, geofence associations, alert rules, event listeners/actions, reports, and dashboard overview.

Meaningful backend/full-stack work completes with:

```bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

Frontend work completes with:

```bash
npm run format
npm run format:check
npm run lint:check
npm run types:check
npm run build
```

## 17. Current architectural checkpoint

Completed active frontend domains: Fleets, Vehicles, Drivers, Devices.

Next domain: Live Tracking. The intended sequence is types/service → page/filter/current-position UI → map/markers → history/trail, while preserving the existing shared frontend architecture.

Remaining frontend domains are Geofences, Alerts/Alert Rules, Reports, and live Dashboard integration.

## 18. Architectural principles

- FleetTrack owns business semantics; Traccar is an external tracking engine.
- Authorization/tenancy precedes external access.
- Controllers stay thin.
- Form Requests own HTTP validation.
- Actions own application operations.
- External integrations remain behind services/DTOs/jobs.
- Async jobs protect against stale desired state.
- Frontend modules reuse the shared layout, UI, API client, auth state, design tokens, and Wayfinder.
- Source code is the final authority when docs drift.
