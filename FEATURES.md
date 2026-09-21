# FleetTrack Features

Status reflects the source snapshot supplied on 2026-09-21.

## Status legend

- **Implemented** — present in current source and used/tested.
- **Frontend pending** — backend exists but no active Vue module/web route yet.
- **Partial** — UI exists but still needs live backend integration.

## Authentication and access — Implemented

Backend:

- Sanctum login, current-user, and logout endpoints.
- Bearer-token API authentication.
- Company-scoped authorization using Spatie Permission teams.
- Role/permission seeders and policy coverage.

Frontend:

- Login page.
- Token persistence and restoration.
- Shared authenticated API client.
- Authenticated application shell and logout.

## Application shell and design system — Implemented

- Responsive `AppLayout`.
- Desktop/mobile sidebar and header/footer.
- Semantic design tokens in `resources/css/app.css`.
- Shared buttons, cards, form controls, table, pagination, badges, loading/error/empty states, and page headers.
- Wayfinder-generated typed routes/actions.

## Companies

Backend: **Implemented** CRUD API, tenancy model, policies, factory/seeding support.

Frontend: **No standalone company-management page currently exposed.**

## Fleets — Implemented backend + frontend

Backend CRUD API and authorization are implemented.

Frontend `/fleets` includes API-backed listing, create/edit/delete, pagination, loading/error/empty states, refresh, validation errors, and responsive presentation.

## Vehicles — Implemented backend + frontend

Frontend `/vehicles` includes CRUD, fleet selection, validation, pagination, status presentation, and responsive states. Vehicle request/database constraints include VIN uniqueness and odometer normalization.

## Drivers — Implemented backend + frontend

Frontend `/drivers` includes CRUD, fleet assignment, validation, pagination, loading/error/empty states, and responsive presentation.

## Devices — Implemented backend + frontend

Backend includes CRUD plus Traccar device synchronization infrastructure.

Frontend `/devices` includes CRUD, vehicle assignment, device status, Traccar synchronization metadata, validation, pagination, and responsive states.

Current device status values are:

```text
active
inactive
maintenance
```

Seeded devices intentionally remain unsynchronized with Traccar (`traccar_device_id` and `last_sync_at` are null).

## Live Tracking — Backend implemented, frontend pending

Current endpoints:

```text
GET /api/v1/tracking/positions
GET /api/v1/tracking/vehicles/{vehicle}
GET /api/v1/tracking/vehicles/{vehicle}/positions
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

The backend supports company visibility, fleet/vehicle filtering, current positions, online/offline interpretation from GPS fix time, position history, trip summaries, and trips.

Next planned frontend milestone: tracking types/service, `/tracking` page, fleet/vehicle filters, live position presentation, then map/markers and history/trail work.

## Geofences — Backend implemented, frontend pending

Implemented:

- Geofence CRUD.
- Traccar geofence synchronization jobs/services.
- Geofence ↔ vehicle association endpoints.
- Stale-job/current-desired-state protection.
- Relationship and service/job tests.

Development seeds create three geofences per customer company with overlapping vehicle associations.

## Alerts — Backend implemented, frontend pending

Implemented:

- Persistent alerts.
- Alert list/show.
- Acknowledge action/API.
- Overspeed, geofence, ignition, and device-offline event handling.
- Traccar webhook translation/listeners.

Development seeds create representative acknowledged and unacknowledged alerts.

## Custom Alert Rules — Backend implemented, frontend pending

Supported seeded/event rule types include:

```text
overspeed
geofence_enter
geofence_exit
ignition_on
ignition_off
device_offline
```

Rules can be company-wide or vehicle-specific. Vehicle-specific rules are resolved ahead of company-wide rules when applicable.

## Reports — Backend implemented, frontend pending

Vehicle report endpoints:

```text
trips
trip-summary
stops
events
route
summary
hours
combined
```

Reports reuse the Traccar/report boundary and enforce FleetTrack authorization/vehicle visibility first.

Export formats remain intentionally undefined until product requirements exist.

## Dashboard — Partial frontend

Backend `GET /api/v1/dashboard/overview` is implemented and tested.

The current Vue Dashboard visual components exist, but the page is not yet wired to the overview API. Live dashboard data remains a frontend task.

## Database, factories, and development seed data — Implemented

`DatabaseSeeder` runs:

```text
PermissionSeeder
CompanySeeder
RoleSeeder
CompanyRoleSeeder
UserSeeder
FleetSeeder
VehicleSeeder
DriverSeeder
DeviceSeeder
GeofenceSeeder
AlertRuleSeeder
AlertSeeder
```

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

The system company receives no operational dummy data. Each of three customer companies receives 2 fleets, 10 vehicles, 10 drivers, 10 devices, 3 geofences, 7 rules, and 6 alerts.

Verified seed invariants include:

- every seeded vehicle has a device;
- every seeded device belongs to a vehicle in the same company;
- every seeded driver belongs to a fleet;
- every seeded geofence has vehicle associations;
- seeded alerts have vehicles;
- fake Traccar IDs/timestamps are not invented for seeded devices/geofences.

## Quality standards — Implemented workflow

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

## Current product status summary

### Backend/API

The planned core backend foundation is substantially implemented: auth, tenancy/authorization, operational CRUD, Traccar synchronization boundaries, tracking, geofences, event ingestion, alerts/rules, reports, and dashboard overview.

### Frontend

Completed active modules:

```text
Login
Dashboard visual shell
Fleets CRUD
Vehicles CRUD
Drivers CRUD
Devices CRUD
```

Pending active modules:

```text
Live Tracking
Geofences
Alerts / Alert Rules
Reports
Dashboard live-data wiring
```

## Next development work

Immediate: **Live Tracking frontend**.

Recommended progression:

```text
tracking types + service
→ page + filters + current positions
→ map + markers/details
→ position history/trail
→ Geofences frontend
→ Alerts / Alert Rules frontend
→ Reports frontend
→ Dashboard API wiring
```
