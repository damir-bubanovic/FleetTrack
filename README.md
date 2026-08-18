# FleetTrack

## Overview

FleetTrack is a multi-tenant fleet management platform built with Laravel. It integrates with Traccar for GPS tracking while keeping FleetTrack as the system of record for business entities such as companies, fleets, users, vehicles, and devices.

The application follows a service-oriented architecture with thin controllers, Action classes for business logic, Policies for authorization, queued integrations, and comprehensive feature tests.

---

# Technology Stack

## Backend

- PHP 8.5
- Laravel 12
- MySQL
- Redis
- Laravel Sail
- Laravel Sanctum
- Spatie Permission (Teams)
- PHPUnit / Pest
- PHPStan (Larastan)
- Laravel Pint

## External Services

- Traccar Server
- Traccar REST API

---

# Current Backend Status

The following modules are implemented and tested.

## Authentication

- API authentication with Laravel Sanctum
- Login
- Logout
- Token-based API access

## Authorization

- Spatie Permission with Teams enabled
- Company isolation
- Policies for API resources
- Middleware that sets the active permission team

## Companies

- CRUD
- Policies
- Validation
- API Resources
- Feature Tests

## Fleets

- CRUD
- Company isolation
- Policies
- Feature Tests

## Vehicles

- CRUD
- Company isolation
- Validation
- Feature Tests

## Devices

- CRUD
- Company isolation
- Validation
- Feature Tests

---

# Traccar Integration

FleetTrack synchronizes devices asynchronously using Laravel queues.

Workflow:

FleetTrack API

↓

Action

↓

Domain Event

↓

Listener

↓

Queued Job

↓

Traccar REST API

Implemented synchronization:

- Create device
- Update device
- Delete device

Synchronization updates:

- Traccar Device ID
- Last synchronization timestamp

The integration uses dedicated classes:

- TraccarClient
- TraccarDeviceService
- DeviceData DTO

---

# Architecture

Business logic is intentionally separated.

- Controllers
- Form Requests
- Policies
- Action classes
- Resources
- Events
- Listeners
- Queue Jobs
- Services
- DTOs

Controllers remain thin while Actions encapsulate business rules.

---

# Multi-Tenancy

The application uses company-based tenancy.

Features include:

- Company-scoped data
- Team-aware permissions
- Policy-based authorization
- Query scopes for visibility

Super Administrators can access all companies.

Company users are restricted to their own company.

---

# Development

## Start environment

```bash
./vendor/bin/sail up -d
```

## Run queues

```bash
./vendor/bin/sail artisan queue:work
```

## Run tests

```bash
./vendor/bin/sail artisan test
```

## Static analysis

```bash
./vendor/bin/sail composer types:check
```

## Code style

```bash
./vendor/bin/sail artisan pint
```

---

# Project Structure

```
app/
 ├── Actions
 ├── Data
 ├── Events
 ├── Http
 ├── Jobs
 ├── Listeners
 ├── Models
 ├── Policies
 ├── Services
```

---

# Development Principles

- Thin Controllers
- Single Responsibility
- Queue external integrations
- Policy-based authorization
- Feature-test driven development
- PHPStan clean
- Laravel Pint compliant

---

# Roadmap

Completed

- Authentication
- Authorization
- Companies
- Fleets
- Vehicles
- Devices
- Traccar synchronization

Next

- Live Tracking
- Positions
- Trips
- Geofences
- Alerts
- Reports
- Dashboard
