# FleetTrack Features

Status reflects the audited source snapshot supplied on 2026-09-28.

## Status legend

- **Implemented** — present in current source and used/tested.
- **Partial** — useful implementation exists, but a product requirement or optional enhancement remains undefined.

## Authentication and access — Implemented

Backend includes Sanctum login/current-user/logout endpoints, bearer-token API authentication, company-scoped authorization with Spatie Permission teams, and role/permission seeders/policy coverage.

Frontend includes login, token persistence/restoration, shared authenticated API access, logout, and the authenticated application shell. API authentication failures render JSON; guest web requests redirect to `web.login`.

## Application shell and design system — Implemented

- Responsive `AppLayout` with desktop/mobile navigation.
- Semantic design tokens in `resources/css/app.css`.
- Shared buttons, cards, form controls, table, pagination, badges, loading/error/empty states, page headers, and icons.
- Wayfinder-generated typed routes/actions.

## Companies — Backend implemented; supporting frontend service implemented

Backend CRUD API, tenancy model, policies, factory/seeding support are implemented.

There is no standalone company-management page. A frontend `Company` type and `companyService.ts` support Super Admin company selection where required, including Geofence creation.

## Fleets — Implemented backend + frontend

`/fleets` provides API-backed listing, create/edit/delete, pagination, loading/error/empty states, refresh, validation errors, and responsive presentation.

## Vehicles — Implemented backend + frontend

`/vehicles` provides CRUD, fleet selection, validation, pagination, status presentation, and responsive states. Vehicle request/database constraints include VIN uniqueness and odometer normalization.

## Drivers — Implemented backend + frontend

`/drivers` provides CRUD, fleet assignment, validation, pagination, loading/error/empty states, and responsive presentation.

## Devices — Implemented backend + frontend

Backend includes CRUD plus Traccar device synchronization infrastructure. A Vehicle can have at most one Device; the database enforces uniqueness for non-null `devices.vehicle_id` while allowing unassigned Devices. Device reassignment reconciles Traccar geofence permissions. `/devices` provides CRUD, vehicle assignment, device status, Traccar synchronization metadata, validation, pagination, and responsive states.

Persisted device statuses are `active`, `inactive`, and `maintenance`. Online/offline on the Tracking page is runtime GPS connectivity state, not a persisted Device status.

## Live Tracking — Implemented backend + frontend

Backend endpoints:

```text
GET /api/v1/tracking/positions
GET /api/v1/tracking/vehicles/{vehicle}
GET /api/v1/tracking/vehicles/{vehicle}/positions
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

Backend supports company visibility, fleet/vehicle filtering, current positions, online/offline interpretation from GPS fix time, position history, trip summaries, and trips.

Frontend `/tracking` includes:

- fleet and vehicle filters for live positions;
- current vehicle count plus online/offline totals;
- current position/status cards;
- Leaflet/OpenStreetMap live map with online/offline markers, tooltips, and popups;
- manual refresh and 30-second polling with overlapping requests prevented; an in-flight request queues one follow-up reload so filter changes are not dropped;
- vehicle/date-range controls for position history;
- historical route rendering with a Leaflet polyline and route start/end markers;
- selected historical-position details;
- loading, error, and empty states for history.

Seeded devices intentionally have no fabricated `traccar_device_id`, so local seeded vehicles normally return no live/history Traccar data until real synchronization/data exists.

## Geofences — Implemented backend + frontend

Backend implemented:

- Geofence CRUD.
- Traccar geofence synchronization jobs/services.
- Geofence ↔ vehicle association endpoints.
- Stale-job/current-desired-state protection.
- Relationship and service/job tests.

Frontend `/geofences` includes:

- paginated API-backed listing;
- create/edit/delete;
- Laravel validation error presentation;
- active/inactive status and Traccar synchronization metadata;
- Super Admin company selector using the actual `super_admin` role value;
- vehicle assignment management;
- interactive Leaflet/OpenStreetMap rendering for circle, polygon, and linestring areas;
- table/map selection and fit-to-geofence behavior.

Area creation/editing still uses the Traccar area/WKT text representation. Interactive boundary drawing/editing is not currently implemented and should only be added if product scope requires it.

## Alerts — Implemented backend + frontend

Backend behavior includes persistent alerts, list/show, acknowledgement, overspeed/geofence/ignition/device-offline event handling, and Traccar webhook translation/listeners.

Frontend `/alerts` provides API-backed alert monitoring, vehicle context, severity/status presentation, acknowledgement, pagination, and immediate UI refresh after acknowledgement. Backend acknowledgement is atomic and only updates an unacknowledged row. Super Admin cross-company acknowledgement is covered by regression testing.

## Custom Alert Rules — Implemented backend + frontend

Supported rule types include `overspeed`, `geofence_enter`, `geofence_exit`, `ignition_on`, `ignition_off`, and `device_offline`. Rules can be company-wide or vehicle-specific; matching vehicle-specific rules take precedence where applicable.

Frontend `/alert-rules` provides rule listing and CRUD management through `alertRuleService.ts` and `AlertRuleForm.vue`.

## Reports — Implemented backend + frontend

Vehicle report endpoints cover trips, trip summary, stops, events, route, summary, hours, and combined reports. FleetTrack authorization/vehicle visibility is enforced before Traccar report access.

Frontend `/reports` provides vehicle/date-range selection and renders all eight report datasets with loading/error/empty states. Seeded vehicles normally return empty Traccar-backed report data until synchronized with real Traccar data.

Report export formats remain undefined until product requirements exist.

## Dashboard — Implemented backend + frontend

`GET /api/v1/dashboard/overview` is implemented and tested. The Vue Dashboard uses `dashboardService.ts` to load real overview data and renders vehicle, online/offline, alert, company, fleet, device, and offline-device metrics rather than static demo values.

## Local Traccar development support — Implemented for current local flows

`LocalTraccarServiceProvider` is registered only in the local environment and fakes the Traccar HTTP calls currently needed by local Tracking/Geofence development: positions, geofence CRUD, and permission attach/detach. Geofences are held in memory by the fake and are not durable Traccar data.

The fake does not manufacture synchronized device IDs or full historical/report datasets for seeded vehicles.

## Database, factories, and development seed data — Implemented

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

The system company receives no operational dummy data. Seeded device/geofence Traccar IDs and sync timestamps remain null until actual synchronization occurs.

## Reliability and data-integrity hardening — Implemented

The audited source includes the following cross-cutting correctness protections:

- queued Traccar jobs re-read current local state and guard against stale device/geofence relationship writes;
- local delete/event ordering prevents remote deletion from being queued before the local delete succeeds;
- malformed Traccar collection entries are ignored rather than assumed to be valid arrays;
- live-position matching accepts numeric Traccar device IDs represented as numbers or numeric strings;
- invalid/future GPS fix timestamps do not incorrectly mark Vehicles online;
- trip-summary calculations ignore invalid coordinates and malformed timestamps;
- Company/Fleet/Vehicle selector helpers load every accessible API page instead of truncating at 100 records;
- paginated management UI disables pagination controls while the current page request is loading;
- deleting the sole item on a later page reloads the previous valid page;
- Company and Fleet newest-first pagination has an `id DESC` tie-breaker for deterministic page traversal.

## Current product status summary

### Completed active frontend modules

```text
Login
Dashboard overview
Fleets CRUD
Vehicles CRUD
Drivers CRUD
Devices CRUD
Live Tracking + position history route trail
Geofences CRUD + vehicle assignments + interactive map
Alert Rules CRUD
Alerts monitoring + acknowledgement
Reports dashboard
```

### Product-scope-dependent / not yet defined

```text
Standalone Company management UI
Interactive Geofence boundary drawing/editing
Report export formats
Additional trip/history interaction beyond the implemented route trail
```

## Next development work

The 2026-09-28 project-wide audit is complete and the final backend/frontend quality gates were reported green. The previously identified backend-backed frontend gaps are implemented. The next feature should be selected from explicit product requirements rather than from the old frontend backlog.

Before adding a new domain feature, prefer one of these evidence-driven directions only when required:

```text
real Traccar integration/demo-data workflow for live/history/report browser testing
interactive Geofence drawing/editing
report export contract + UI
standalone Company administration
additional tracking/trip UX
```

## Quality standards

Backend/full-stack gate:

```bash
sail composer lint
sail composer lint:check
sail composer types:check
sail artisan test
```

Frontend gate:

```bash
sail npm run format
sail npm run format:check
sail npm run lint:check
sail npm run types:check
sail npm run build
```
