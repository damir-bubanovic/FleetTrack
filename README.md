# FleetTrack

FleetTrack is a multi-tenant fleet-management and GPS-tracking application built with Laravel, Vue, Inertia, and Traccar.

Laravel owns the business domain, tenancy, authorization, alerts, reporting contracts, and application UI. Traccar is the external GPS engine for live positions, position history, trips, stops, geofences, devices, and supported tracking events.

## Current checkpoint — 2026-09-23

### Backend

Implemented and covered by the current API/test suite:

- Sanctum authentication and authenticated-user endpoints.
- Company-scoped authorization with Spatie Permission teams.
- Companies, fleets, drivers, vehicles, devices, geofences, and alert-rule CRUD APIs.
- Device synchronization with Traccar.
- Geofence synchronization and geofence ↔ vehicle association.
- Live positions, single-vehicle position, position history, trip summary, and trips.
- Traccar webhook ingestion for supported events.
- Persistent alerts, acknowledgement, and custom alert-rule resolution.
- Vehicle reports: trips, trip summary, stops, events, route, summary, hours, and combined report.
- Dashboard overview API.
- API authentication failures render JSON rather than attempting the web login route.

### Frontend

Implemented web modules:

- Login and bearer-token authentication restoration/logout.
- Responsive application shell and reusable UI layer.
- Dashboard visual UI; live overview API wiring is still pending.
- Fleets: API-backed list/create/edit/delete, validation, pagination, refresh, and responsive states.
- Vehicles: API-backed list/create/edit/delete, fleet selection, validation, pagination, and responsive states.
- Drivers: API-backed list/create/edit/delete, fleet assignment, validation, pagination, and responsive states.
- Devices: API-backed list/create/edit/delete, vehicle assignment, device status, synchronization metadata, validation, pagination, and responsive states.
- Live Tracking: API-backed fleet/vehicle filters, current-position/status cards, online/offline totals, Leaflet/OpenStreetMap map, online/offline markers, tooltips/popups, manual refresh, and 30-second polling without resetting the user's map viewport on ordinary position refreshes.
- Geofences: API-backed list/create/edit/delete, pagination, validation, status/synchronization metadata, and Super Admin company selection.

Current web routes:

```text
/login       Login
/            Dashboard
/fleets      Fleets
/vehicles    Vehicles
/drivers     Drivers
/devices     Devices
/tracking    Live Tracking
/geofences   Geofences
```

The next frontend work is to finish the remaining Geofence UX (vehicle assignment and map-based boundary editing if required), then Alerts/Alert Rules, Reports, and live Dashboard wiring. Tracking history/trail UI also remains available as a follow-up to the current-position map.

## Technology stack

### Backend

```text
PHP ^8.3
Laravel ^13.17
Laravel Sanctum
Spatie Laravel Permission with Teams
MySQL
Redis
Laravel Sail
Pest 4
PHPStan / Larastan
Laravel Pint
Traccar REST API
```

### Frontend

```text
Vue 3
TypeScript
Inertia.js 3
Tailwind CSS 4
Vite 8
Laravel Wayfinder
Leaflet 1.9 + OpenStreetMap tiles
vue-tsc
ESLint
Prettier
```

## Local development

```bash
composer install
./vendor/bin/sail up -d
npm install
cp .env.example .env
sail artisan key:generate
sail artisan migrate:fresh --seed
sail artisan wayfinder:generate
npm run dev
```

If `sail` is configured as a shell alias, use `sail ...` as shown throughout the project.

### Seeded development login

```text
Email: admin@fleettrack.test
Password: password
```

These credentials are for local seeded development only.

## Development seed data

`DatabaseSeeder` creates a connected development dataset suitable for UI work after every `migrate:fresh --seed`.

Expected totals:

```text
Companies       4
Fleets          6
Vehicles       30
Drivers        30
Devices        30
Geofences       9
Alert Rules    21
Alerts          18
```

`FleetTrack Logistics` is the system company and intentionally has no operational dummy fleet data. Each of the three customer companies receives 2 fleets, 10 vehicles, 10 drivers, 10 devices, 3 geofences, 7 alert rules, and 6 representative alerts.

Seeded devices and geofences intentionally have `traccar_*_id = null` and `last_sync_at = null`; local seed data must not pretend fake entities exist in a real Traccar server.

## Local Traccar development fake

In the `local` environment, `AppServiceProvider` registers `LocalTraccarServiceProvider`. It uses Laravel HTTP fakes for the currently implemented local UI flows:

- `/api/positions` returns generated positions for locally synchronized devices.
- Geofence create/read/update/delete requests are held in an in-memory fake store.
- Traccar permission attach/detach calls return `204`.

This is a development harness, not production behavior. The in-memory geofence fake resets with the PHP process and should not be treated as persistent Traccar storage.

## Authentication

Backend endpoints:

```text
POST /api/v1/auth/login
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

The frontend persists the Sanctum personal-access token under `fleettrack_auth_token`. `authState` restores the user through `/auth/me`; the shared API client clears invalid authentication on `401`.

API requests are configured to render authentication failures as JSON. Guest web requests redirect to the named `web.login` route.

## Frontend structure

```text
resources/js/
├── components/
│   ├── app/
│   ├── dashboard/
│   ├── devices/
│   ├── drivers/
│   ├── fleets/
│   ├── geofences/
│   ├── tracking/
│   ├── vehicles/
│   └── ui/
├── layouts/
├── pages/
│   ├── Auth/
│   ├── Devices/
│   ├── Drivers/
│   ├── Fleets/
│   ├── Geofences/
│   ├── Tracking/
│   └── Vehicles/
├── services/
├── types/
├── routes/       # generated by Wayfinder
└── actions/      # generated by Wayfinder
```

Feature services/types now exist for auth, companies (supporting company selection), fleets, vehicles, drivers, devices, tracking, and geofences. New frontend domains should follow the same service/type/component pattern and use the shared `apiRequest()` helper.

## API summary

### Tracking

```text
GET /api/v1/tracking/positions
GET /api/v1/tracking/vehicles/{vehicle}
GET /api/v1/tracking/vehicles/{vehicle}/positions
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

### Reports

```text
GET /api/v1/reports/vehicles/{vehicle}/trips
GET /api/v1/reports/vehicles/{vehicle}/trip-summary
GET /api/v1/reports/vehicles/{vehicle}/stops
GET /api/v1/reports/vehicles/{vehicle}/events
GET /api/v1/reports/vehicles/{vehicle}/route
GET /api/v1/reports/vehicles/{vehicle}/summary
GET /api/v1/reports/vehicles/{vehicle}/hours
GET /api/v1/reports/vehicles/{vehicle}/combined
```

### Dashboard

```text
GET /api/v1/dashboard/overview
```

REST resources also exist for companies, fleets, drivers, vehicles, devices, geofences, and alert rules; alerts provide list/show/acknowledge endpoints. Geofence ↔ vehicle association endpoints also exist.

## Quality gates

For a meaningful backend/full-stack slice:

```bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

For frontend work:

```bash
npm run format
npm run format:check
npm run lint:check
npm run types:check
npm run build
```

Run the checks relevant to the changed slice before committing. Browser verification is also required for user-facing frontend work.

## Development rules

- Source code is authoritative when documentation is stale.
- Keep controllers thin; use Form Requests, Actions, Services, Resources, policies, and DTOs according to the existing architecture.
- Enforce tenant visibility before requesting external Traccar data.
- Do not hand-edit generated Wayfinder files; regenerate them after route changes.
- Reuse `AppLayout`, shared UI components, semantic tokens, `authState`, and `apiRequest()`.
- Complete meaningful features with quality gates and then commit with a concise message.

## Documentation

- `README.md` — setup and current checkpoint.
- `FEATURES.md` — feature-by-feature implementation status and remaining work.
- `ARCHITECTURE.md` — current system architecture.
- `AGENTS.md` — coding/workflow guidance for future sessions.
- `docs/ARCHITECTURE_DECISIONS.md` — durable architectural decisions.
