# Architecture Decisions

## Purpose

This document records the major architectural decisions made during FleetTrack development and the reasoning behind them.

---

# ADR-001: Laravel as the Backend Framework

**Decision**

Use Laravel 12 as the primary backend framework.

**Rationale**

- Mature ecosystem
- Excellent testing support
- Queue system
- Policies
- First-party authentication
- Strong community

---

# ADR-002: Company-Based Multi-Tenancy

**Decision**

Use company-based tenancy instead of separate databases.

**Rationale**

- Simpler deployment
- Easier reporting across tenants
- Lower operational complexity

Implementation:

- `company_id` on business entities
- Policy-based authorization
- Visibility scopes
- Spatie Permission Teams

---

# ADR-003: Thin Controllers

**Decision**

Controllers orchestrate requests only.

Business rules belong in Action classes.

**Benefits**

- Easier testing
- Better separation of concerns
- Reusable business logic

---

# ADR-004: Actions for Business Logic

Examples:

- CreateCompany
- UpdateFleet
- CreateVehicle
- CreateDevice
- UpdateDevice
- DeleteDevice

Actions may dispatch domain events after successful persistence.

---

# ADR-005: Event-Driven Traccar Synchronization

**Decision**

Synchronize Traccar asynchronously.

Flow:

FleetTrack
→ Action
→ Event
→ Listener
→ Queue Job
→ Traccar API

**Benefits**

- Faster API responses
- Retry support
- Loose coupling
- Better fault tolerance

---

# ADR-006: Dedicated Traccar Service Layer

External API communication is isolated behind:

- TraccarClient
- TraccarDeviceService
- DeviceData DTO

This keeps controllers and jobs independent of HTTP implementation details.

---

# ADR-007: Queue Jobs Have a Single Responsibility

Current jobs:

- SyncDeviceToTraccar
- UpdateDeviceInTraccar
- DeleteDeviceFromTraccar

Each job performs one operation and reports failures through logging and exception reporting.

---

# ADR-008: Policy-Based Authorization

Authorization is implemented with Laravel Policies rather than controller conditionals.

This keeps authorization centralized and testable.

---

# ADR-009: Feature-Test Driven Development

Every API module should include feature tests covering:

- Success paths
- Validation
- Authorization
- Tenant isolation
- Queue dispatching (using Queue::fake() where appropriate)

---

# ADR-010: Static Analysis and Code Quality

The project maintains:

- PHPStan (Larastan) with zero errors
- Laravel Pint formatting
- Automated feature tests

New code should preserve these quality gates.

---

# Current Baseline

Implemented:

- Authentication
- Authorization
- Companies
- Fleets
- Users
- Vehicles
- Devices
- Traccar device synchronization

Next planned module:

**Live Tracking**
