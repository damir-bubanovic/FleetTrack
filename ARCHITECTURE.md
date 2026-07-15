# FleetTrack — Architecture

## 1. Purpose

This document defines the technical architecture, development conventions, authorization model, and structural guidelines for FleetTrack.

FleetTrack is designed as a scalable multi-company fleet management and GPS tracking platform built with Laravel, Vue.js, Flutter, Traccar, MySQL, Redis, and Docker.

The architecture should remain:

* Maintainable
* Testable
* Secure
* Scalable
* Familiar to Laravel developers
* Suitable for both single-client deployments and future SaaS operation

---

# 2. System Overview

FleetTrack consists of the following primary components:

```text
Web Dashboard
    │
    ▼
Laravel Application
    │
    ├── MySQL
    ├── Redis
    ├── Queue Workers
    ├── REST API
    └── Traccar Integration
            │
            ▼
       Traccar Server
            │
            ▼
     GPS Devices / Mobile Apps
```

## Main applications

### Laravel Backend

Responsible for:

* Authentication
* Authorization
* Company management
* User management
* Driver management
* Vehicle management
* GPS device management
* Trip management
* Alerts
* Reports
* API endpoints
* Traccar synchronization
* Background jobs
* Notifications

### Vue.js Web Dashboard

Responsible for:

* Administrative interface
* Fleet management
* Live maps
* Vehicle and driver management
* Reports
* Alerts
* Company settings

### Flutter Mobile Application

Responsible for:

* Driver authentication
* Assigned vehicle information
* Background GPS tracking
* Start and stop tracking
* Trip history
* Vehicle issue reporting
* Driver notifications

### Traccar Server

Responsible for:

* GPS device communication
* Position collection
* Device events
* Geofence events
* Tracking protocol support
* Real-time location data

---

# 3. Application Type

FleetTrack is designed as a multi-company platform.

Multiple logistics companies may use the same FleetTrack installation while keeping their data isolated.

Each company owns its own:

* Users
* Drivers
* Vehicles
* GPS devices
* Trips
* Geofences
* Alerts
* Reports
* Settings

A company must never access another company's data.

---

# 4. User Hierarchy

FleetTrack supports four primary roles.

## Super Admin

A global platform administrator.

Responsibilities include:

* Managing all companies
* Managing platform-level users
* Viewing platform-wide information
* Managing system configuration
* Supporting client companies
* Accessing application and activity logs

A Super Admin may exist without a `company_id`.

## Company Admin

An administrator belonging to one company.

Responsibilities include:

* Managing company details
* Managing company users
* Managing fleet managers
* Managing drivers
* Managing vehicles
* Managing GPS devices
* Managing company settings
* Viewing reports and alerts

## Fleet Manager

An operational user belonging to one company.

Responsibilities include:

* Managing drivers
* Managing vehicles
* Managing GPS devices
* Managing trips
* Managing geofences
* Monitoring live tracking
* Reviewing alerts
* Viewing and exporting reports

## Driver

A mobile-focused user belonging to one company.

Responsibilities include:

* Viewing the assigned vehicle
* Starting and stopping tracking
* Viewing personal trip history
* Reporting vehicle issues
* Receiving relevant notifications

---

# 5. Authentication

Laravel authentication will be used for the web application.

Laravel Sanctum will be used for API and mobile authentication.

Authentication responsibilities include:

* Login
* Logout
* Password reset
* Email verification
* Session management
* API token management
* Active account validation

Inactive users must not be allowed to authenticate.

The `last_login_at` field should be updated after successful authentication.

---

# 6. Authorization

FleetTrack uses three authorization layers.

```text
Roles
    ↓
Permissions
    ↓
Policies and Company Isolation
```

## Roles and Permissions

The project uses:

```text
spatie/laravel-permission
```

Spatie team support is enabled.

The team foreign key is:

```text
company_id
```

Roles are stored in the database rather than directly in the `users` table.

Main authorization tables include:

```text
roles
permissions
model_has_roles
model_has_permissions
role_has_permissions
```

## Permission naming

Permissions use the following convention:

```text
resource.action
```

Examples:

```text
vehicles.view
vehicles.create
vehicles.update
vehicles.delete

drivers.view
drivers.create
drivers.update
drivers.delete

reports.view
reports.export
```

Application code should check permissions rather than hard-coding role names.

Preferred:

```php
$user->can('vehicles.update');
```

Avoid:

```php
$user->hasRole('fleet_manager');
```

Role checks should only be used when behavior is explicitly tied to a role rather than a capability.

---

# 7. Company Isolation

Permissions determine what a user is allowed to do.

Policies determine which records the user is allowed to access.

A user may have the `vehicles.update` permission but may only update vehicles belonging to the same company.

Example policy rule:

```php
public function update(User $user, Vehicle $vehicle): bool
{
    return $user->can('vehicles.update')
        && $user->company_id === $vehicle->company_id;
}
```

Super Admin access may bypass company isolation where explicitly required.

Global scopes should not be used blindly for all company filtering because they can hide data unexpectedly in administrative tasks, queues, console commands, and tests.

Company restrictions should be enforced through:

* Policies
* Query scopes
* Service methods
* Form Requests
* Explicit repository or query conditions

---

# 8. Laravel Project Structure

FleetTrack stays close to Laravel's standard structure.

```text
app/
├── Actions/
├── DTOs/
├── Enums/
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Models/
├── Notifications/
├── Observers/
├── Policies/
├── Providers/
├── Services/
└── ValueObjects/
```

Folders should only be introduced when they contain real application code.

Empty architectural folders should not be created prematurely.

---

# 9. Models

Models represent database entities and define:

* Relationships
* Attribute casts
* Query scopes
* Simple domain helpers

Models should not contain large workflows or complex business logic.

Examples of appropriate model methods:

```php
$user->isActive();

$vehicle->isAvailable();

$trip->isCompleted();
```

Examples of logic that should not live in a model:

* Multi-step Traccar synchronization
* Complex report generation
* Vehicle assignment workflows
* External API requests
* Large transactional operations

That logic belongs in Actions or Services.

---

# 10. Enums

Enums are stored in:

```text
app/Enums
```

Enums should be used for stable domain values such as:

* User roles
* Vehicle status
* Driver status
* Device status
* Trip status
* Fuel type
* Alert type
* Alert severity

Example:

```php
enum VehicleStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Maintenance = 'maintenance';
}
```

Enum cases should use PascalCase.

Database values should use lowercase snake case.

---

# 11. Controllers

Controllers should remain small.

Controllers are responsible for:

* Receiving HTTP requests
* Authorizing actions
* Calling Actions or Services
* Returning views or API responses

Controllers should not contain:

* Large database queries
* External API integrations
* Complex validation
* Multi-step business workflows

Example:

```php
public function store(StoreVehicleRequest $request, CreateVehicle $action)
{
    $vehicle = $action->handle(
        $request->user(),
        $request->validated(),
    );

    return new VehicleResource($vehicle);
}
```

---

# 12. Form Requests

Validation and request-level authorization belong in Form Request classes.

Stored in:

```text
app/Http/Requests
```

Responsibilities include:

* Validating incoming data
* Normalizing request values
* Performing request-level permission checks
* Returning clear validation messages

Controllers should use validated data only.

---

# 13. Actions

Actions represent a single business operation.

Stored in:

```text
app/Actions
```

Examples:

```text
CreateCompany
CreateVehicle
AssignDriverToVehicle
RegisterGpsDevice
StartTrip
CompleteTrip
SynchronizeTraccarDevice
```

An Action should usually expose one public method:

```php
public function handle(...): mixed
```

Actions are preferred for focused use cases and transactional workflows.

---

# 14. Services

Services handle broader reusable domain or integration logic.

Stored in:

```text
app/Services
```

Examples:

```text
TraccarService
TrackingService
ReportService
NotificationService
FileUploadService
```

Services may be called by:

* Actions
* Jobs
* Controllers
* Console commands

Services should not depend directly on HTTP requests.

---

# 15. DTOs

Data Transfer Objects may be used when passing structured data between layers.

Stored in:

```text
app/DTOs
```

DTOs are recommended when:

* An Action accepts many related values
* Data comes from an external API
* Traccar payloads require normalization
* Strong typing improves clarity

DTOs should not replace simple validated arrays unless they provide clear value.

---

# 16. Policies

Policies are stored in:

```text
app/Policies
```

Every company-owned resource should eventually have a policy.

Examples:

```text
CompanyPolicy
UserPolicy
DriverPolicy
VehiclePolicy
DevicePolicy
TripPolicy
GeofencePolicy
AlertPolicy
```

Policies should enforce:

* Permission checks
* Company ownership
* Record-level restrictions
* Super Admin exceptions

---

# 17. API Design

FleetTrack uses a REST API.

API routes should be versioned.

Example:

```text
/api/v1/vehicles
/api/v1/drivers
/api/v1/trips
/api/v1/devices
```

API responses should use Laravel API Resources.

Stored in:

```text
app/Http/Resources
```

API Resources provide:

* Consistent response structures
* Controlled field exposure
* Relationship formatting
* Date formatting
* Future API compatibility

---

# 18. Database Conventions

## Naming

Database tables use plural snake case:

```text
companies
users
vehicles
gps_devices
vehicle_assignments
```

Foreign keys use singular snake case:

```text
company_id
user_id
vehicle_id
driver_id
```

## Company ownership

Company-owned tables should normally contain:

```text
company_id
```

This makes data isolation explicit and improves query performance.

## Status fields

Stable status fields should use string-backed enums.

## Timestamps

Standard business tables should contain:

```text
created_at
updated_at
```

Entities requiring recoverability should use soft deletes.

## Foreign keys

Foreign keys should use explicit delete behavior.

Examples:

```php
->cascadeOnDelete();
->restrictOnDelete();
->nullOnDelete();
```

Delete behavior should be selected according to business rules rather than applied automatically.

## Indexes

Indexes should be added for frequently queried columns such as:

```text
company_id
status
is_active
device_id
vehicle_id
driver_id
started_at
completed_at
```

Composite indexes should reflect real query patterns.

---

# 19. Traccar Integration

Traccar integration should be isolated from controllers and models.

Recommended structure:

```text
app/
├── DTOs/
│   └── Traccar/
├── Jobs/
│   └── Traccar/
├── Services/
│   └── TraccarService.php
└── Actions/
    └── Traccar/
```

Traccar responsibilities include:

* Device synchronization
* Position synchronization
* Event synchronization
* Geofence synchronization
* Live position retrieval
* Device status updates

External Traccar identifiers should be stored separately from FleetTrack primary keys.

Example:

```text
id
company_id
traccar_device_id
unique_identifier
```

Traccar API calls should use:

* Timeouts
* Error handling
* Logging
* Retry strategies
* Queue jobs where appropriate

---

# 20. Queues and Background Jobs

Long-running work should be processed asynchronously.

Examples:

* Position synchronization
* Traccar event imports
* Report generation
* Email notifications
* Bulk data exports
* Device synchronization
* Alert processing

Jobs are stored in:

```text
app/Jobs
```

Jobs should be:

* Idempotent where possible
* Retry-safe
* Logged on failure
* Scoped to the correct company

Redis will be used as the queue backend.

---

# 21. Events and Notifications

Events should represent meaningful application occurrences.

Examples:

```text
VehicleAssigned
DriverAssigned
TripStarted
TripCompleted
DeviceWentOffline
GeofenceEntered
OverspeedDetected
```

Listeners may trigger:

* Notifications
* Logging
* Alert creation
* WebSocket broadcasts
* External synchronization

Notifications may be delivered through:

* Database
* Email
* Push notifications
* In-app notifications

---

# 22. Caching

Redis may be used for:

* Permission caching
* Session storage
* Queue storage
* Frequently accessed dashboard data
* Live location snapshots
* Traccar synchronization locks

Company-specific cache keys must include the company identifier.

Example:

```text
company:15:dashboard:statistics
```

---

# 23. Logging

Important system activity should be logged.

Examples:

* Authentication attempts
* Company changes
* User changes
* Vehicle assignments
* Device registration
* Traccar synchronization failures
* Administrative actions

Sensitive values must not be logged.

Examples include:

* Passwords
* API tokens
* Personal access tokens
* Traccar credentials
* Session identifiers

---

# 24. Testing

FleetTrack should include:

* Unit tests
* Feature tests
* API tests
* Authorization tests
* Company isolation tests
* Traccar integration tests
* Queue job tests

Critical authorization scenarios must be tested explicitly.

Examples:

```text
Company A user cannot view Company B vehicle
Driver cannot delete a vehicle
Fleet Manager can update a company vehicle
Super Admin can view all companies
Inactive user cannot authenticate
```

Factories should provide realistic test data.

Tests should not depend on seeded production-style data unless explicitly designed as integration tests.

---

# 25. Code Style

FleetTrack follows Laravel and PSR conventions.

Formatting should be handled with Laravel Pint.

Run:

```bash
sail pint
```

Before committing major changes, run:

```bash
sail artisan test
sail pint --test
```

Naming conventions:

```text
Models: singular PascalCase
Controllers: ResourceController
Requests: StoreResourceRequest
Policies: ResourcePolicy
Actions: VerbResource
Services: DomainService
Enums: Singular PascalCase
Permissions: plural-resource.action
```

---

# 26. Git Conventions

Commits should represent completed, working units of functionality.

Recommended commit format:

```text
type(scope): description
```

Examples:

```text
feat(auth): implement company-scoped role authorization
feat(company): add company management module
feat(vehicle): implement vehicle CRUD
fix(traccar): handle unavailable device responses
test(auth): add company isolation tests
docs(architecture): document application architecture
```

Avoid committing broken or partially migrated features unless using a dedicated development branch.

---

# 27. Development Workflow

For each feature:

```text
Database design
    ↓
Migration
    ↓
Model and relationships
    ↓
Factory and seeder
    ↓
Permissions and policy
    ↓
Form Requests
    ↓
Action or Service
    ↓
Controller and routes
    ↓
API Resource or frontend integration
    ↓
Tests
    ↓
Documentation
    ↓
Git commit
```

Major milestones should be committed only after:

* Migrations succeed
* Seeders succeed
* Tests pass
* Formatting passes
* Main workflows are manually verified

---

# 28. Current Architecture Decisions

The following decisions are currently active:

- Laravel standard folder structure
- Multi-company architecture
- Nullable `company_id` for global Super Admin users
- Spatie Laravel Permission with Teams
- Laravel Policies for record-level authorization
- Laravel Sanctum (planned for API authentication)
- Redis for queues and caching
- Traccar integration through Services and Jobs (planned)
- PHP Enums for domain states
- Company ownership via `company_id`
- Docker development with Laravel Sail

---

# 29. Future Considerations

Potential future capabilities include:

* Subscription plans
* Billing
* Company self-registration
* White-label branding
* Multiple depots per company
* Multiple user memberships across companies
* Advanced permission customization
* WebSocket live tracking
* Route optimization
* Maintenance scheduling
* Fuel management
* Driver behavior scoring
* Internationalization
* Audit log package integration

These capabilities should not be implemented until required, but current architecture should avoid blocking them.

---

# 30. Guiding Principle

FleetTrack should favor clear, conventional Laravel code over unnecessary abstraction.

Architecture should grow in response to real requirements.

The objective is not to create the largest possible structure.

The objective is to create a secure, understandable, testable, and maintainable fleet management platform.


---

# 31. Current Project Milestone

## ✅ Milestone 1 Completed

The following foundation has been successfully implemented:

- Laravel project initialization
- Docker (Laravel Sail) development environment
- Multi-company database architecture
- Company model
- User model
- User ↔ Company relationship
- Spatie Laravel Permission integration
- Role & Permission system
- Company-scoped authorization
- Database factories
- Database seeders
- Project documentation
- Code formatting with Laravel Pint
- Passing migrations
- Passing tests

## Next Milestone

Chapter 2 – Company Management

- Company CRUD
- Company settings
- Company logo
- Company policies
- Company Form Requests
- Company API Resources
- Company tests