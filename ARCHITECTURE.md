# FleetTrack Architecture

## Overview

FleetTrack is a modern multi-tenant fleet management and GPS tracking platform built with Laravel 12.

The application follows an API-first architecture with strong separation of concerns, reusable business components, automated testing, and secure tenant isolation.

The Fleet, Driver, and Vehicle modules establish the architectural reference implementation for all future business modules.

---

# Architectural Principles

## API-First

All business functionality is exposed through versioned REST endpoints.

```
/api/v1
```

The API is consumed by:

- Vue.js web application
- Flutter mobile application
- Third-party integrations
- Future public API

---

## Multi-Tenant Architecture

FleetTrack supports multiple logistics companies within a single application.

Each business entity belongs to a company through:

```
company_id
```

Tenant isolation is enforced through:

- Laravel Policies
- Spatie Permission Teams
- Query scopes
- Business Actions
- Authorization checks

---

## Thin Controllers

Controllers have a single responsibility:

- Authorize requests
- Delegate work to Actions
- Return API Resources

Controllers never contain business logic.

Example flow:

```
Request
    ↓
Controller
    ↓
Policy
    ↓
Action
    ↓
Model
    ↓
API Resource
    ↓
JSON Response
```

---

## Action-Based Business Logic

Business rules live inside dedicated Action classes.

Example:

```
CreateFleet
UpdateFleet
DeleteFleet

CreateDriver
UpdateDriver
DeleteDriver

CreateVehicle
UpdateVehicle
DeleteVehicle
```

Advantages:

- Small controllers
- Reusable business logic
- Easier testing
- Better separation of concerns

---

# Project Structure

```
app/

├── Actions/
│
├── Enums/
│
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
│
├── Models/
│   └── Concerns/
│
├── Policies/
│
├── Providers/
│
└── Support/
```

Every business module follows the same structure.

---

# Domain Model

```
Company
│
├── Users
│
├── Fleets
│   │
│   ├── Drivers
│   │
│   └── Vehicles
│
└── Future
    ├── Devices
    ├── Trips
    ├── Alerts
    └── Geofences
```

---

# Authorization

FleetTrack combines Laravel Policies with Spatie Permission.

## Roles

- Super Admin
- Company Admin
- Fleet Manager
- Driver

Permissions are assigned through roles.

Company-specific permissions are provisioned automatically.

---

# Reusable Components

## BelongsToCompany

Shared model trait providing:

- Company relationship
- Tenant query scope
- Shared ownership behavior

---

## visibleTo()

Reusable query scope used by controllers.

Responsibilities:

- Super Admin sees all records
- Company Admin sees only company records

This removes duplicated tenant filtering logic.

---

## Shared Testing Traits

Reusable testing helpers include:

- CreatesCompanies
- CreatesUsers
- CreatesFleets
- CreatesDrivers
- CreatesVehicles

These eliminate duplicated setup code across feature tests.

---

# Request Validation

Every write endpoint uses Form Requests.

Responsibilities:

- Validation
- Sanitization
- Request authorization

Business rules remain inside Actions.

---

# API Resources

Every endpoint returns dedicated API Resources.

Benefits:

- Consistent JSON
- Stable API contracts
- Easier frontend development

---

# Testing Strategy

FleetTrack follows a feature-test-first workflow.

Each completed module includes automated tests covering:

- CRUD operations
- Authorization
- Validation
- Multi-tenant isolation
- Business rules

Current completed test suites:

- Company
- Fleet
- Driver
- Vehicle

---

# Development Workflow

Every module follows the same implementation process.

```
Migration
↓

Model
↓

Factory
↓

Seeder
↓

Policy
↓

Form Requests
↓

Actions
↓

API Resource
↓

Controller
↓

Routes
↓

Feature Tests
↓

Refactoring
↓

Documentation
↓

Git Commit
```

This workflow ensures every module is implemented consistently.

---

# Current Reference Modules

The following modules are complete and serve as implementation references:

## Fleet

Reference for:

- CRUD
- Policies
- Actions
- Resources
- Requests
- Testing

## Driver

Reference for:

- Fleet relationships
- Company ownership
- Authorization

## Vehicle

Reference for:

- Fleet ownership validation
- Business rule enforcement
- CRUD testing
- Resource consistency

Future modules should follow these established patterns.

---

# Future Architecture

Upcoming modules:

- GPS Device Management
- Traccar Integration
- Live Tracking
- Trip Management
- Geofencing
- Alerts
- Dashboard
- Reports

Each will follow the same architecture established by the Fleet, Driver, and Vehicle modules.

---

# Conclusion

FleetTrack is designed around:

- Clean Architecture
- API-First Development
- Multi-Tenant Security
- Reusable Components
- Automated Testing
- Incremental Refactoring

The architecture intentionally favors consistency over complexity, making future modules faster to implement while maintaining high code quality.