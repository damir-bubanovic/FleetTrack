# FleetTrack Architecture

## Overview

FleetTrack is a multi-tenant fleet management platform built with Laravel. The application is the system of record for business entities, while Traccar provides GPS tracking capabilities. External communication with Traccar is asynchronous through Laravel Events, Listeners, and Queue Jobs.

---

# High-Level Architecture

```
Client
   │
   ▼
API Routes
   │
   ▼
Controllers
   │
   ▼
Form Requests ─── Policies
   │
   ▼
Actions
   │
   ├── Eloquent Models
   ├── Domain Events
   └── Services
           │
           ▼
      Queue Jobs
           │
           ▼
     Traccar REST API
```

---

# Architectural Principles

- Thin controllers
- Business logic in Action classes
- Validation in Form Requests
- Authorization through Policies
- API Resources for responses
- External integrations through Services
- Asynchronous processing with queues
- Feature-test driven development

---

# Multi-Tenancy

FleetTrack uses company-based tenancy.

## Isolation

- Every business entity belongs to a company.
- Policies enforce company ownership.
- Query scopes restrict visibility.
- Spatie Permission Teams use `company_id` as the active team.

Super Administrators have global access.

---

# Domain Structure

## Models

- Company
- Fleet
- User
- Vehicle
- Device

Future modules:

- Position
- Trip
- Geofence
- Alert
- Report

---

# HTTP Layer

Each module follows the same pattern:

- API Controller
- Form Requests
- API Resource
- Policy
- Action classes

Controllers orchestrate requests but do not contain business rules.

---

# Business Layer

Action classes encapsulate application logic.

Examples:

- CreateCompany
- UpdateVehicle
- CreateDevice
- UpdateDevice
- DeleteDevice

Actions may dispatch events after successful persistence.

---

# Event-Driven Integration

Device synchronization is implemented using Laravel events.

```
Create Device
      │
      ▼
DeviceCreated
      │
      ▼
Listener
      │
      ▼
Queue Job
      │
      ▼
Traccar API
```

The same pattern is used for:

- DeviceCreated
- DeviceUpdated
- DeviceDeleted

---

# Queue Architecture

Redis is used as the queue backend.

Jobs:

- SyncDeviceToTraccar
- UpdateDeviceInTraccar
- DeleteDeviceFromTraccar

Each job:

- retries failed requests
- logs failures
- reports exceptions
- performs one responsibility

---

# Traccar Integration

Core classes:

- TraccarClient
- TraccarDeviceService
- DeviceData DTO

Responsibilities:

## TraccarClient

- HTTP communication
- Authentication
- Timeouts
- JSON configuration

## TraccarDeviceService

- Device CRUD
- Payload normalization
- DTO conversion

---

# Testing Strategy

- Pest feature tests
- Queue::fake() for feature tests involving jobs
- Policy verification
- Multi-tenant authorization checks
- Database assertions
- PHPStan static analysis
- Laravel Pint formatting

---

# Current Status

Completed:

- Authentication
- Authorization
- Companies
- Fleets
- Vehicles
- Devices
- Traccar synchronization

Next:

- Live Tracking
- Positions
- Trips
- Geofences
- Alerts
- Reports
