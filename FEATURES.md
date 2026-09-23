# FleetTrack Features

Status reflects the source snapshot supplied on 2026-09-23.

## Status legend

- **Implemented** — present in current source and used/tested.
- **Frontend pending** — backend exists but no active Vue module/web route yet.
- **Partial** — useful UI exists but additional planned integration remains.

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

There is no standalone company-management page. A frontend `Company` type and `companyService.ts` now exist to support Super Admin company selection in Geofence forms.

## Fleets — Implemented backend + frontend

`/fleets` provides API-backed listing, create/edit/delete, pagination, loading/error/empty states, refresh, validation errors, and responsive presentation.

## Vehicles — Implemented backend + frontend

`/vehicles` provides CRUD, fleet selection, validation, pagination, status presentation, and responsive states. Vehicle request/database constraints include VIN uniqueness and odometer normalization.

## Drivers — Implemented backend + frontend

`/drivers` provides CRUD, fleet assignment, validation, pagination, loading/error/empty states, and responsive presentation.

## Devices — Implemented backend + frontend

Backend includes CRUD plus Traccar device synchronization infrastructure. `/devices` provides CRUD, vehicle assignment, device status, Traccar synchronization metadata, validation, pagination, and responsive states.

Persisted device statuses are `active`, `inactive`, and `maintenance`. Online/offline on the Tracking page is runtime GPS connectivity state, not a persisted Device status.

## Live Tracking — Current-position frontend implemented; history/trail UI pending

Backend endpoints:

```text
GET /api/v1/tracking/positions
GET /api/v1/tracking/vehicles/{vehicle}
GET /api/v1/tracking/vehicles/{vehicle}/positions
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

Backend supports company visibility, fleet/vehicle filtering, current positions, online/offline interpretation from GPS fix time, position history, trip summaries, and trips.

Frontend `/tracking` now includes:

- fleet and vehicle filters;
- current vehicle count plus online/offline totals;
- current position/status cards;
- Leaflet/OpenStreetMap map;
- online/offline markers;
- vehicle-name tooltips and detail popups;
- fit-to-vehicle behavior without continually resetting the viewport when only positions change;
- manual refresh;
- automatic refresh every 30 seconds with overlapping requests prevented.

Remaining Tracking work: expose position history/trail and any trip/history interaction required by the product.

## Geofences — CRUD frontend implemented; association/map UX pending

Backend implemented:

- Geofence CRUD.
- Traccar geofence synchronization jobs/services.
- Geofence ↔ vehicle association endpoints.
- Stale-job/current-desired-state protection.
- Relationship and service/job tests.

Frontend `/geofences` now includes:

- paginated API-backed listing;
- create/edit/delete;
- Laravel validation error presentation;
- active/inactive status;
- Traccar geofence ID and last-sync metadata;
- Super Admin company selector using the actual `super_admin` role value;
- company API support for the selector.

Current area editing is a Traccar area/WKT text field. Remaining Geofence UX includes vehicle assignment management and map-based boundary drawing/editing if required by product scope.

## Alerts — Backend implemented, frontend pending

Implemented backend behavior includes persistent alerts, list/show, acknowledgement, overspeed/geofence/ignition/device-offline event handling, and Traccar webhook translation/listeners.

## Custom Alert Rules — Backend implemented, frontend pending

Supported rule types include `overspeed`, `geofence_enter`, `geofence_exit`, `ignition_on`, `ignition_off`, and `device_offline`. Rules can be company-wide or vehicle-specific; matching vehicle-specific rules take precedence where applicable.

## Reports — Backend implemented, frontend pending

Vehicle report endpoints cover trips, trip-summary, stops, events, route, summary, hours, and combined reports. FleetTrack authorization/vehicle visibility is enforced before Traccar report access.

Export formats remain undefined until product requirements exist.

## Dashboard — Partial frontend

`GET /api/v1/dashboard/overview` is implemented and tested. Dashboard visual components exist, but the page is not yet wired to the overview API.

## Local Traccar development support — Implemented for current UI flows

`LocalTraccarServiceProvider` is registered only in the local environment and fakes the Traccar HTTP calls currently needed by local Tracking/Geofence development: positions, geofence CRUD, and permission attach/detach. Geofences are held in memory by the fake and are not durable Traccar data.

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

## Current product status summary

### Completed active frontend modules

```text
Login
Fleets CRUD
Vehicles CRUD
Drivers CRUD
Devices CRUD
Live Tracking current-position map
Geofences CRUD
```

### Partial

```text
Dashboard visual shell (live API wiring pending)
Live Tracking (history/trail UI pending)
Geofences (vehicle-association UI and optional map editor pending)
```

### Frontend pending

```text
Alerts / Alert Rules
Reports
```

## Next development work

Recommended progression from the current checkpoint:

```text
finish Geofence UX as required (vehicle associations / map boundary editor)
→ Alerts / Alert Rules frontend
→ Reports frontend
→ Dashboard overview API wiring
→ Tracking history/trail enhancements
```

The exact order can change with product priority; backend contracts for these domains already exist.

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
npm run format
npm run format:check
npm run lint:check
npm run types:check
npm run build
```
