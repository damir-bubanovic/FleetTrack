# FleetTrack

## Overview

FleetTrack is a multi-tenant fleet management and GPS tracking platform built with Laravel.

FleetTrack is the system of record for business entities such as companies, fleets, users, drivers, vehicles, devices, and geofences. It integrates with Traccar for GPS tracking, position history, GPS trip detection, geofence synchronization, and device/geofence permission associations.

The backend follows a layered architecture with thin controllers, Action classes for application logic, Form Requests for validation, Policies and team-aware permissions for authorization, API Resources for response contracts, and dedicated services for Traccar integration.

---

# Technology Stack

## Backend

- PHP `^8.3`
- Laravel `^13.17`
- MySQL
- Redis
- Laravel Sail
- Laravel Sanctum
- Spatie Laravel Permission with Teams
- Pest
- PHPStan / Larastan
- Laravel Pint

## External Services

- Traccar Server
- Traccar REST API

---

# Current Backend Status

The core fleet-management backend, tracking foundation, and the current Geofence implementation are implemented and tested.

## Authentication

- API login
- Authenticated user endpoint
- Logout
- Laravel Sanctum authentication
- Protected API routes

Current authentication endpoints:

```text
POST /api/v1/auth/login
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

## Authorization and Multi-Tenancy

- Spatie Laravel Permission with Teams
- Company-based tenant isolation
- Policy-based authorization
- Team-aware permission middleware
- Visibility scopes
- Super Administrator access
- Company Administrator access
- Tracking authorization through `tracking.view`
- Geofence authorization through `geofences.*` permissions

## Companies

- CRUD
- Validation
- Policies
- API Resources
- Feature tests

## Fleets

- CRUD
- Company ownership
- Tenant isolation
- Policies
- Feature tests

## Drivers

- Company-scoped driver backend
- Actions
- Validation
- API Resources
- Authorization
- Feature coverage

## Vehicles

- CRUD
- Fleet assignment
- Company isolation
- Validation
- Authorization
- Feature tests
- Many-to-many Geofence associations

## Devices

- CRUD
- Vehicle assignment
- Company isolation
- Validation
- Authorization
- Traccar synchronization
- Association reconciliation after Traccar synchronization
- Feature and job tests

## Geofences

The Geofence module is currently under active development. Its local CRUD, Traccar lifecycle synchronization, and Vehicle associations are implemented.

Implemented:

- Full CRUD API
- Company ownership and tenant isolation
- Validation
- Policy/permission authorization
- API Resource contract
- Local `Geofence` model and factory
- Traccar geofence ID and synchronization timestamp
- Asynchronous create/update/delete synchronization with Traccar
- Many-to-many Geofence ↔ Vehicle association
- Tenant-safe attach/detach API
- Idempotent attach/detach behavior
- Traccar device/geofence permission synchronization
- Protection against stale attach/detach queue jobs
- Reconciliation after a Geofence receives its Traccar ID
- Reconciliation after a Device receives its Traccar ID
- Focused API, Action, relationship, service, and queue-job tests

Current Geofence API includes the CRUD resource plus Vehicle association endpoints:

```text
GET        /api/v1/geofences
POST       /api/v1/geofences
GET        /api/v1/geofences/{geofence}
PUT/PATCH  /api/v1/geofences/{geofence}
DELETE     /api/v1/geofences/{geofence}

POST       /api/v1/geofences/{geofence}/vehicles/{vehicle}
DELETE     /api/v1/geofences/{geofence}/vehicles/{vehicle}
```

The next Geofence slice is entry/exit event handling from Traccar and the application behavior that should follow those events.

---

# Traccar Integration

All Traccar HTTP communication is isolated behind dedicated integration services.

Current core integration classes include:

- `TraccarClient`
- `TraccarDeviceService`
- `TraccarGeofenceService`
- `PositionService`
- `ReportService`
- `DeviceData`
- `GeofenceData`

## Device Synchronization

Device lifecycle writes are synchronized asynchronously through Laravel queues.

```text
FleetTrack API
    ↓
Action
    ↓
Domain Event
    ↓
Listener
    ↓
Queue Job
    ↓
TraccarDeviceService
    ↓
Traccar REST API
```

Implemented synchronization includes:

- Create device
- Update device
- Delete device
- Store Traccar device ID
- Track synchronization state/timestamp
- Reconcile existing Geofence associations after initial Device synchronization

## Geofence Synchronization

Geofence lifecycle writes follow the same asynchronous integration pattern.

```text
FleetTrack API
    ↓
Geofence Action
    ↓
Geofence Event
    ↓
Listener
    ↓
Queue Job
    ↓
TraccarGeofenceService
    ↓
Traccar REST API
```

Implemented synchronization includes:

- Create Geofence in Traccar
- Update Geofence in Traccar
- Delete Geofence from Traccar
- Store Traccar geofence ID
- Track synchronization timestamp
- Reconcile existing Vehicle associations after initial Geofence synchronization

## Geofence ↔ Vehicle Permission Synchronization

FleetTrack stores the business-domain association as Geofence ↔ Vehicle. It does not expose a Traccar-specific Geofence ↔ Device relationship as the CRM domain model.

For Traccar synchronization, FleetTrack resolves:

```text
Geofence
    ↓
traccar_geofence_id

Vehicle
    ↓
Device
    ↓
traccar_device_id
```

The resulting Traccar permission is synchronized through `/api/permissions`.

Attach flow:

```text
AttachVehicleToGeofence
    ↓
VehicleAttachedToGeofence
    ↓
SyncVehicleAttachedToGeofenceToTraccar
    ↓
AttachGeofenceToDeviceInTraccar
    ↓
TraccarGeofenceService::attachDevice()
```

Detach follows the corresponding detach event/listener/job flow.

Queue jobs re-check current local state before writing to Traccar. This prevents stale queued attach/detach jobs from overwriting a newer association state.

Because Device and Geofence creation are asynchronous, association synchronization is also reconciled when either side later receives its Traccar ID.

## Tracking Reads

Tracking and report reads are synchronous because the API caller requires the current Traccar result.

```text
FleetTrack API
    ↓
Tracking Action
    ↓
PositionService / ReportService
    ↓
Traccar REST API
    ↓
API Resource
    ↓
JSON Response
```

Traccar is the source of truth for GPS position data and detected GPS trips.

---

# Tracking API

The current tracking API contains five endpoints.

## Live Positions

```text
GET /api/v1/tracking/positions
```

Provides:

- Latest positions for visible synchronized devices
- Fleet filtering
- Vehicle filtering
- Combined filters
- Company isolation
- Online/offline status
- Last-seen information

## Vehicle Live Position

```text
GET /api/v1/tracking/vehicles/{vehicle}
```

Provides the latest Traccar position for a visible vehicle with a synchronized device.

## Vehicle Position History

```text
GET /api/v1/tracking/vehicles/{vehicle}/positions
```

Provides historical vehicle positions for a requested date range.

Current validation:

- `from` required
- `to` required
- `to` must be after `from`
- Maximum range: 7 days

## Vehicle Trip Summary

```text
GET /api/v1/tracking/vehicles/{vehicle}/trip-summary
```

Calculates aggregate statistics over a requested position-history range.

Current output includes:

- Position count
- Start/end time
- Duration in seconds
- Distance in kilometers
- Average sampled speed
- Maximum speed
- Moving time
- Stopped time
- Speed unit (`knots`)

This endpoint summarizes a selected range; it does not detect individual trips.

## Vehicle Trip History

```text
GET /api/v1/tracking/vehicles/{vehicle}/trips
```

Returns trips detected by Traccar through `/reports/trips`.

FleetTrack applies tenant visibility and transforms the external report into its API contract.

The normalized response includes:

- Start/end times
- Start/end coordinates
- Distance in kilometers
- Duration in seconds
- Average speed
- Maximum speed
- Speed unit (`knots`)
- Start/end addresses
- Traccar device/driver references where available

FleetTrack intentionally delegates GPS trip detection to Traccar rather than maintaining a competing detection algorithm.

---

# Architecture

The application separates responsibilities into:

```text
Routes
    ↓
Middleware
    ↓
Form Requests
    ↓
Controllers
    ↓
Policies / Permissions
    ↓
Actions
    ↓
Models / Services
    ↓
API Resources
```

Additional asynchronous integration components include:

- Events
- Listeners
- Queue Jobs
- DTOs

Controllers remain thin while Actions contain application/business logic.

See `ARCHITECTURE.md` for the detailed architecture, synchronization flows, and eventual-consistency rules.

---

# Multi-Tenancy

FleetTrack uses company-based tenancy.

Core rules:

- Company users access only data visible to their company.
- Super Administrators can have global visibility.
- Team-aware permissions are configured with Spatie Permission.
- Policies centralize authorization.
- Visibility scopes restrict database queries.
- Cross-company Geofence ↔ Vehicle associations are rejected.
- Traccar data is exposed only after resolving an authorized FleetTrack entity.

External Traccar identifiers never bypass FleetTrack tenant authorization.

---

# Development

FleetTrack uses Laravel Sail.

## Start the environment

```bash
sail up -d
```

If the `sail` shell alias is not configured:

```bash
./vendor/bin/sail up -d
```

## Run the queue worker

```bash
sail artisan queue:work
```

## Run a targeted test

Example:

```bash
sail artisan test tests/Feature/Api/Geofence/GeofenceVehicleControllerTest.php
```

## Formatting

Apply Laravel Pint formatting through the Composer script:

```bash
sail composer lint
```

Check formatting without modifying files:

```bash
sail composer lint:check
```

## Static analysis

```bash
sail composer types:check
```

## Full test suite

```bash
sail artisan test
```

Before committing a meaningful functionality slice, the expected quality gate is:

```bash
sail composer lint:check
sail composer types:check
sail artisan test
```

If `lint:check` reports Pint-fixable formatting issues, run `sail composer lint` and then rerun the complete quality gate before committing.

---

# Project Structure

```text
app/
├── Actions/
├── Data/
├── Events/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Listeners/
├── Models/
├── Policies/
└── Services/
    └── Traccar/
```

Tests are primarily organized under feature-oriented paths, including API and focused unit-style feature tests for Actions, Models, Jobs, and Services.

---

# Development Principles

- Thin controllers
- Business logic in Actions
- Validation in Form Requests
- Policy/permission-based authorization
- Strict tenant isolation
- External APIs behind services
- Asynchronous Device and Geofence synchronization
- Event/listener/job integration flows
- Re-check current local state in asynchronous association jobs
- Reconcile asynchronous dependencies when external IDs become available
- Synchronous tracking/report reads
- Stable API Resource contracts
- Focused tests for new behavior
- PHPStan clean
- Laravel Pint compliant
- Full test suite green before meaningful commits
- Focused Git commits

---

# Documentation

Project documentation:

- `README.md` — project overview and development entry point
- `FEATURES.md` — implemented functionality, current module status, and roadmap
- `ARCHITECTURE.md` — detailed current architecture and integration flows
- `AGENTS.md` — contributor rules and fresh-session continuation context
- `docs/ARCHITECTURE_DECISIONS.md` — durable architectural decisions

The latest source code remains the primary source of truth if documentation and implementation ever differ.

---

# Roadmap

## Completed Foundations

- Authentication
- Authorization and multi-tenancy
- Companies
- Fleets
- Drivers
- Vehicles
- Devices
- Traccar device synchronization
- Live Tracking
- Online/offline tracking status
- Vehicle position history
- Vehicle aggregate trip summary
- Traccar-detected vehicle trip history

## Current Module — Geofences

Completed within the Geofence module:

- CRUD
- Company ownership and authorization
- Traccar create/update/delete synchronization
- Geofence ↔ Vehicle associations
- Traccar device/geofence permission synchronization
- Stale-job protection
- Device/Geofence synchronization reconciliation

Next Geofence work:

1. Geofence entry/exit event handling
2. Application-side handling of those events
3. Notifications where required by product requirements
4. Final Geofence module tests/documentation checkpoint

## Remaining Modules

1. Alerts
2. Reports
3. Dashboard

---

# Current Development Checkpoint

The latest completed functionality slice is **Geofence ↔ Vehicle association and Traccar permission synchronization**.

At this checkpoint:

- Geofence CRUD is implemented.
- Geofence lifecycle changes synchronize asynchronously with Traccar.
- Vehicles can be attached to and detached from Geofences through the FleetTrack API.
- Cross-company associations are rejected.
- Traccar device/geofence permissions synchronize asynchronously.
- Attach/detach operations are idempotent.
- Stale queued association jobs re-check current state before changing Traccar.
- Associations are reconciled when either a Device or Geofence receives its Traccar ID after the association already exists.
- Focused association, service, API, and synchronization job tests pass.
- Laravel Pint formatting/lint checks pass.
- PHPStan / Larastan reports no errors.
- The full test suite passes.
- The Geofence ↔ Vehicle association and Traccar synchronization slice has been committed.

The next implementation slice is **Geofence entry/exit event handling**. After the Geofence module is complete, development proceeds to **Alerts**, then **Reports**, then **Dashboard**.
