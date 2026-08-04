# FleetTrack Architecture

FleetTrack follows a modular, API-first architecture designed around
Laravel best practices, multi-company data isolation, and long-term
maintainability.

------------------------------------------------------------------------

# Core Principles

-   API-first development
-   Thin Controllers
-   Action-based business logic
-   Form Request validation
-   Laravel Policies for authorization
-   API Resources for responses
-   Multi-company architecture
-   Automated Feature Testing
-   Incremental refactoring

------------------------------------------------------------------------

# Request Lifecycle

``` text
HTTP Request
      │
      ▼
Form Request
      │
      ▼
Policy
      │
      ▼
Action
      │
      ▼
Model
      │
      ▼
API Resource
      │
      ▼
JSON Response
```

------------------------------------------------------------------------

# Multi-Company Architecture

FleetTrack is a multi-company application.

Business entities belong to a company using `company_id`.

Tenant isolation is enforced through:

-   Laravel Policies
-   `BelongsToCompany` trait
-   `visibleTo()` scope
-   `forCompany()` scope
-   Spatie Permission Teams

A dedicated internal FleetTrack company is used for global Super Admin
role assignments.

------------------------------------------------------------------------

# Backend Layers

## Models

Represent business entities and relationships.

## Form Requests

Validate incoming requests.

## Policies

Authorize user access.

## Actions

Contain business logic.

Examples:

-   CreateFleet
-   UpdateFleet
-   DeleteFleet
-   CreateDriver
-   UpdateDriver
-   DeleteDriver

## API Resources

Transform models into consistent JSON responses.

## Controllers

Coordinate requests, authorization, actions and resources.

Controllers intentionally avoid business logic.

------------------------------------------------------------------------

# Module Structure

Each CRUD module follows the same structure.

``` text
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
StoreRequest
UpdateRequest
    ↓
CreateAction
UpdateAction
DeleteAction
    ↓
API Resource
    ↓
Controller
    ↓
Feature Tests
```

Current completed reference modules:

-   Company
-   Fleet
-   Driver

------------------------------------------------------------------------

# Shared Components

## BelongsToCompany

Provides:

-   Company relationship
-   `forCompany()` scope
-   `visibleTo()` scope

## ProvisionCompanyRoles

Creates company-scoped roles and synchronizes permissions.

------------------------------------------------------------------------

# Testing Strategy

FleetTrack uses Pest and Laravel Feature Tests.

Every module follows:

1.  Build functionality
2.  Write Feature Tests
3.  Make tests pass
4.  Refactor
5.  Commit

Reusable testing traits currently include:

-   CreatesCompanies
-   CreatesFleets
-   CreatesDrivers
-   CreatesUsers

------------------------------------------------------------------------

# Development Philosophy

FleetTrack follows these engineering principles:

-   Prefer Laravel conventions.
-   Keep controllers thin.
-   Place business rules in Actions.
-   Keep authorization in Policies.
-   Validate with Form Requests.
-   Return API Resources.
-   Extract reusable abstractions only after repeated use.
-   Refactor continuously while keeping all tests green.

For the reasoning behind these choices, see `ARCHITECTURE_DECISIONS.md`.

------------------------------------------------------------------------

# Current Architecture Status

Completed:

-   Multi-company foundation
-   Authentication & authorization
-   Company module
-   Fleet module
-   Driver module
-   Company visibility scopes
-   Shared testing infrastructure

Next:

-   Vehicle module
-   GPS Devices
-   Trips
-   Geofencing
-   Alerts
-   Reporting
-   Flutter application
