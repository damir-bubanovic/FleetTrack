# FleetTrack Architecture

## Overview

FleetTrack is a multi-tenant fleet management and GPS tracking platform
built with Laravel, Vue.js, Flutter, and Traccar. The backend follows an
API-first architecture with a strong emphasis on separation of concerns,
automated testing, and reusable components.

------------------------------------------------------------------------

# 1. Architectural Principles

## API-first

All business functionality is exposed through versioned REST endpoints
(`/api/v1`) and consumed by both the web frontend and future Flutter
mobile application.

## Multi-tenancy

Each logistics company owns its own business data. Tenant isolation is
enforced throughout the application using:

-   `company_id`
-   Laravel Policies
-   Spatie Permission Teams
-   Query scopes

## Thin Controllers

Controllers are responsible only for:

-   Authorization
-   Delegating business logic to Actions
-   Returning API Resources

They do not contain business rules.

## Action-based Business Logic

Business logic is encapsulated in dedicated Action classes.

Example:

-   CreateFleet
-   UpdateFleet
-   DeleteFleet

This keeps controllers small and makes business logic reusable and
testable.

------------------------------------------------------------------------

# 2. Technology Stack

-   Laravel 12
-   PHP 8.5
-   Vue.js
-   Flutter
-   Traccar
-   MySQL
-   Redis
-   Docker (Laravel Sail)
-   Pest

------------------------------------------------------------------------

# 3. Project Structure

``` text
app/
├── Actions/
├── Enums/
├── Http/
│   ├── Controllers/Api/
│   ├── Requests/
│   └── Resources/
├── Models/
│   └── Concerns/
├── Policies/
└── Providers/
```

Each business module follows the same structure.

------------------------------------------------------------------------

# 4. Multi-Tenant Architecture

## Company Ownership

Business entities belong to a company using `company_id`.

## System Company

FleetTrack maintains an internal system company used for Super
Administrator role assignments.

## Spatie Teams

Spatie Permission Teams uses `company_id` as the team context.

## Tenant Isolation

Tenant isolation is enforced through:

-   Policies
-   Model query scopes
-   Actions
-   Authorization checks

------------------------------------------------------------------------

# 5. Authorization

FleetTrack uses Laravel Policies together with Spatie Permission.

## Roles

-   Super Admin
-   Company Admin
-   Fleet Manager
-   Driver

## Permissions

Permissions are assigned through roles. Company-specific roles are
provisioned using the `ProvisionCompanyRoles` service.

------------------------------------------------------------------------

# 6. Backend Layers

## Models

Represent business entities and relationships.

## Requests

Validate incoming API requests.

## Policies

Determine authorization.

## Actions

Contain business rules.

## API Resources

Transform models into JSON responses.

## Controllers

Coordinate requests, authorization, actions, and resources.

------------------------------------------------------------------------

# 7. Module Structure

Every module follows the same pattern.

``` text
Module
├── Model
├── Policy
├── StoreRequest
├── UpdateRequest
├── CreateAction
├── UpdateAction
├── DeleteAction
├── Resource
├── API Controller
└── Feature Tests
```

The Fleet module is the reference implementation for all future modules.

------------------------------------------------------------------------

# 8. Reusable Components

## BelongsToCompany

Reusable trait providing:

-   Company relationship
-   Company query scope

## ProvisionCompanyRoles

Creates company-scoped roles and synchronizes permissions.

------------------------------------------------------------------------

# 9. Testing Strategy

FleetTrack follows a Test-Driven Development workflow.

## Testing Framework

-   Pest
-   Laravel Feature Tests

## Principles

-   Write feature tests for every endpoint.
-   Refactor only after all tests pass.
-   Commit only after meaningful milestones.

Reusable testing helpers exist for:

-   Users
-   Companies
-   Fleets

------------------------------------------------------------------------

# 10. Development Workflow

1.  Design the module.
2.  Implement file by file.
3.  Write feature tests.
4.  Make all tests pass.
5.  Refactor.
6.  Commit milestone.
7.  Push to GitHub.

------------------------------------------------------------------------

# 11. Current Reference Module

Fleet Management is the completed reference module.

It demonstrates:

-   CRUD API
-   Multi-tenant authorization
-   Policies
-   Form Requests
-   Actions
-   API Resources
-   Soft Deletes
-   Reusable model traits
-   Automated feature testing

Future modules (Drivers, Vehicles, Devices, Trips, Geofences, Alerts)
will follow the same architecture.

------------------------------------------------------------------------

# 12. Future Roadmap

Upcoming modules:

-   Driver Management
-   Vehicle Management
-   GPS Device Management
-   Trip Management
-   Geofencing
-   Alerts
-   Reports
-   Traccar Integration
-   Flutter Mobile Application

------------------------------------------------------------------------

# Conclusion

FleetTrack is designed around clean architecture, reusable components,
multi-tenant security, and automated testing. The completed Fleet module
establishes the implementation standard for all future development.
