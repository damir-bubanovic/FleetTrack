# FleetTrack Architecture

This document describes the architecture present in the source snapshot supplied on 2026-09-25. Source code remains authoritative.

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

Frontend: Vue 3, TypeScript, Inertia 3, Tailwind CSS 4, Vite 8, Wayfinder, Leaflet 1.9, OpenStreetMap tiles, vue-tsc, ESLint, Prettier.

## 3. Backend layering

```text
Route
→ Controller
→ Form Request / authorization
→ Action
→ Service or Eloquent domain operation
→ API Resource
```

Controllers stay thin. HTTP validation belongs in Form Requests. Business operations live in Actions. External Traccar calls are isolated behind services/DTOs/jobs. Resources define API output shapes.

## 4. Authentication and authorization

Laravel Sanctum personal access tokens back the web/API authentication flow. The Vue client persists the bearer token and restores the authenticated user through `/api/v1/auth/me`.

Spatie Laravel Permission uses teams for company-scoped roles/permissions. Tenant visibility is checked before external Traccar reads.

`bootstrap/app.php` distinguishes API and web guest behavior: API failures render JSON and do not attempt a login redirect; guest web requests redirect to `web.login`.

Role values are backend enum values such as `super_admin`; presentation code may humanize them, but authorization checks must use the actual stored value.

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

Device and Geofence synchronization use the existing service/job infrastructure. `traccar_*_id` and `last_sync_at` represent synchronization state. Geofence ↔ Vehicle relationships are translated to Traccar device/geofence permissions when external IDs are available. Async jobs verify current desired state to avoid stale writes.

Tracking/report reads are synchronous because callers need current external results; FleetTrack authorization runs before external access.

## 7. Local Traccar development boundary

`AppServiceProvider` registers `LocalTraccarServiceProvider` only in the `local` environment. It uses `Http::fake()` to support current local UI development without requiring a live Traccar server for every interaction.

Currently faked:

```text
GET    /api/positions
POST   /api/geofences
GET    /api/geofences/{id}
PUT    /api/geofences/{id}
DELETE /api/geofences/{id}
POST/DELETE /api/permissions
```

Position data is generated from local Devices that have a `traccar_device_id`. Geofence fake records are stored in memory and reset with the process. This provider is development-only and is not a persistence model.

## 8. Traccar events and alerts

Authenticated Traccar webhook events are translated into FleetTrack behavior. Supported paths include overspeed, geofence transitions, ignition changes, and device-offline events.

Alerts are persistent FleetTrack entities and can be acknowledged. Alert rules can be company-wide or vehicle-specific; matching vehicle-specific rules take precedence where applicable.

## 9. Tracking architecture

`LiveTrackingController` exposes current positions, single-vehicle position, position history, trip summary, and trips.

Frontend `/tracking` uses `trackingService.ts`, `LiveTrackingMap.vue`, and `HistoricalTrackingMap.vue`. It combines Fleet and Vehicle APIs for filters with the tracking positions endpoint for current data. The page polls every 30 seconds, prevents overlapping live-position requests, and supports manual refresh.

`LiveTrackingMap.vue` owns the live Leaflet map/marker lifecycle. It fits bounds on initial render or when the vehicle set changes, avoiding unwanted viewport resets when the same vehicles merely move. Markers expose online/offline styling, tooltips, and popups.

Position history is loaded synchronously for a selected vehicle/date range through the existing authorization-safe backend endpoint. `HistoricalTrackingMap.vue` renders valid coordinates as a route polyline with start/end markers and emits selected positions for detail presentation. Seeded devices intentionally lack fabricated Traccar IDs, so an empty history state is normal until real Traccar synchronization/data exists.

## 10. Geofence architecture

Backend Geofence CRUD and synchronization remain authoritative. The Vue `/geofences` page uses `geofenceService.ts` for paginated CRUD and `GeofenceForm.vue` for validation-aware editing.

Super Admin creation requires explicit `company_id`; the frontend loads companies through `companyService.ts` and renders a Company selector only when the authenticated role contains `super_admin`. Company Admin behavior continues to rely on backend tenancy rules.

The frontend includes Geofence ↔ Vehicle assignment management and `GeofenceMap.vue` for interactive Leaflet rendering/selection of circle, polygon, and linestring areas. Area creation/editing still uses the Traccar area representation as text; interactive boundary drawing/editing is not implemented and remains product-scope dependent.

## 11. Reports architecture

`ReportController` exposes vehicle trips, trip summary, stops, events, route, summary, hours, and a combined report. Report actions reuse authorization and Traccar report infrastructure rather than creating a parallel data source.

The Vue `/reports` page uses `reportService.ts` to load all eight datasets for a selected vehicle/date range and presents structured loading/error/empty/result states. Traccar remains the report data source. Report export remains undefined pending product requirements.

## 12. Dashboard architecture

`GetDashboardOverview` supplies aggregate counts/status information through `GET /api/v1/dashboard/overview`. The Vue Dashboard uses `dashboardService.ts` and renders API-backed vehicle, online/offline, alert, company, fleet, device, and offline-device metrics.

## 13. Frontend architecture

Active pages:

```text
Auth/Login.vue
Dashboard.vue
Fleets/Index.vue
Vehicles/Index.vue
Drivers/Index.vue
Devices/Index.vue
Tracking/Index.vue
Geofences/Index.vue
AlertRules/Index.vue
Alerts/Index.vue
Reports/Index.vue
```

Feature modules follow:

```text
page
→ feature component/form
→ feature service
→ shared apiRequest()
→ Laravel API
```

Current feature services/types exist for authentication, companies, fleets, vehicles, drivers, devices, tracking, geofences, alert rules, alerts, reports, and dashboard overview.

## 14. Shared frontend infrastructure

Application components: `AppLogo`, `AppHeader`, `AppSidebar`, `AppFooter`, and `AppLayout`.

Shared UI includes buttons, cards, inputs, selects, textareas, tables, pagination, status badges, form fields, page headers, loading/error/empty states, and icons. Semantic design tokens are centralized in `resources/css/app.css`.

## 15. Wayfinder

Laravel route/action helpers under `resources/js/routes` and `resources/js/actions` are generated artifacts. Laravel route definitions are authoritative. After route changes:

```bash
sail artisan wayfinder:generate
```

Generated files are not hand-edited.

## 16. Error boundaries

- Laravel Form Requests own user-correctable request validation.
- Frontend `ApiError` preserves `422` field errors.
- Unexpected server details are not surfaced verbatim in normal frontend error messages.
- `401` clears invalid frontend auth state.
- API authentication failures render JSON instead of resolving a web login route.
- External Traccar failures remain behind service/action boundaries.
- Tenant checks happen before external reads.

## 17. Database and seed architecture

Expected fresh-seed totals:

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

Each customer company has two fleets with five vehicles and five drivers per fleet. Each vehicle gets one local device. Geofences receive overlapping vehicle assignments. Rules and alerts cover representative event types and acknowledgement states. Fake external Traccar IDs are intentionally not seeded.

## 18. Testing and quality gates

The project has feature/unit coverage across authentication, CRUD APIs, policies, requests, tracking, Traccar services/jobs, geofence associations, alert rules, event listeners/actions, reports, and dashboard overview.

Backend/full-stack gate:

```bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

Frontend gate:

```bash
npm run format
npm run format:check
npm run lint:check
npm run types:check
npm run build
```

Browser verification is required for user-facing work.

## 19. Current architectural checkpoint

Completed active frontend domains: Dashboard overview, Fleets, Vehicles, Drivers, Devices, Live Tracking with position history, Geofences with vehicle assignments/map rendering, Alert Rules, Alerts, and Reports.

The previously identified backend-backed frontend gaps are implemented. Remaining work is product-scope dependent rather than an established frontend backlog; examples include interactive Geofence drawing/editing, report exports, standalone Company administration, additional tracking/trip UX, or a deliberate real-Traccar development/demo workflow.

## 20. Architectural principles

- FleetTrack owns business semantics; Traccar is an external tracking engine.
- Authorization/tenancy precedes external access.
- Controllers stay thin; Form Requests own validation; Actions own application operations.
- External integrations remain behind services/DTOs/jobs.
- Async jobs protect against stale desired state.
- Local HTTP fakes are development infrastructure, not production persistence.
- Frontend modules reuse shared layout/UI/API/auth/design/Wayfinder infrastructure.
- Stored enum/role values are authoritative; display formatting must not be reused as authorization semantics.
- Source code is the final authority when docs drift.
