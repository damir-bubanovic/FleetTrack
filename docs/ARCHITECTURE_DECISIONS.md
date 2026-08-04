# FleetTrack Architecture Decisions

This document records the architectural decisions made during the development of FleetTrack.

Unlike `ARCHITECTURE.md`, which describes the system structure, this document explains **why** specific architectural choices were made and the principles that guide future development.

---

# ADR-001 — Multi-Company Architecture

## Decision

FleetTrack uses a multi-company architecture where nearly every business entity belongs to a company through a `company_id` foreign key.

## Rationale

Fleet management platforms are naturally multi-tenant.

Using explicit company ownership allows:

- complete company data isolation
- simple authorization
- efficient querying
- scalable architecture

Every business entity must belong to exactly one company unless explicitly documented otherwise.

---

# ADR-002 — Thin Controllers

## Decision

Controllers remain thin.

Controllers are responsible only for:

- authorization
- request validation
- invoking Actions
- returning API Resources

Controllers must not contain business logic.

## Rationale

Keeping controllers thin improves readability, maintainability, and testability.

---

# ADR-003 — Business Logic Lives in Actions

## Decision

Business operations are implemented as dedicated Action classes.

Examples:

- CreateFleet
- UpdateFleet
- DeleteFleet
- CreateDriver
- UpdateDriver
- DeleteDriver

## Rationale

Actions encapsulate business logic in reusable, testable classes.

This keeps controllers focused on HTTP concerns and makes business rules reusable across APIs, console commands, queues, and future integrations.

---

# ADR-004 — Authorization Uses Policies

## Decision

Authorization is handled through Laravel Policies.

Business logic must never rely solely on controller authorization.

## Rationale

Policies centralize authorization rules while Actions enforce business rules.

This separation improves maintainability and prevents accidental authorization bypasses.

---

# ADR-005 — Validation Uses Form Requests

## Decision

Incoming request validation is performed using Laravel Form Requests.

Controllers should never contain validation rules.

## Rationale

Validation remains centralized, reusable, and easy to test.

---

# ADR-006 — API Responses Use Resources

## Decision

All API responses are returned through Laravel API Resources.

Models are never returned directly.

## Rationale

Resources provide:

- consistent API responses
- controlled serialization
- future API versioning
- presentation separation

---

# ADR-007 — Company Visibility Uses Model Scopes

## Decision

Company visibility is implemented through reusable Eloquent scopes provided by the `BelongsToCompany` trait.

Examples:

```php
Fleet::query()
    ->visibleTo($user);

Driver::query()
    ->visibleTo($user);
```

## Rationale

Visibility rules belong close to the models they affect.

This avoids duplicated controller logic while remaining idiomatic Laravel.

---

# ADR-008 — Shared Behaviour Uses Traits

## Decision

Reusable model behaviour is implemented through traits.

Example:

- BelongsToCompany

## Rationale

Traits provide reusable behaviour without introducing unnecessary inheritance.

---

# ADR-009 — Feature Tests Drive Development

## Decision

Every CRUD module is developed together with Feature Tests.

Typical workflow:

1. Migration
2. Model
3. Factory
4. Seeder
5. Policy
6. Form Requests
7. Actions
8. Resource
9. Controller
10. Feature Tests

## Rationale

Feature tests verify complete request lifecycles and reduce regressions during refactoring.

---

# ADR-010 — Prefer Laravel Conventions

## Decision

FleetTrack follows Laravel conventions whenever possible.

Avoid introducing custom abstractions unless Laravel's built-in solutions become insufficient.

## Rationale

Laravel already provides expressive solutions for:

- routing
- validation
- authorization
- ORM
- resources
- testing

Using framework conventions reduces complexity and improves maintainability.

---

# ADR-011 — Extract Abstractions Only After Repeated Use

## Decision

Reusable abstractions are introduced only after a pattern has been implemented multiple times.

## Rationale

Premature abstraction often increases complexity.

Patterns should emerge naturally before becoming reusable components.

This principle keeps the codebase simple while reducing genuine duplication.

---

# ADR-012 — Test Helpers Have Single Responsibilities

## Decision

Testing traits remain focused on a single responsibility.

Examples:

- CreatesCompanies
- CreatesFleets
- CreatesDrivers

## Rationale

Single-purpose helpers are easier to understand, extend, and maintain than large utility traits.

---

# Guiding Principles

FleetTrack is guided by the following principles:

- Prefer clarity over cleverness.
- Follow Laravel conventions.
- Keep controllers thin.
- Keep business rules inside Actions.
- Keep authorization inside Policies.
- Keep validation inside Form Requests.
- Prefer composition over inheritance.
- Write Feature Tests alongside new functionality.
- Refactor only after patterns emerge.
- Continuously improve architecture without introducing unnecessary complexity.

---

_Last updated: August 2026_