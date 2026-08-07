# FleetTrack

FleetTrack is a modern multi-tenant fleet management and GPS tracking platform built with **Laravel 12**, **Vue.js**, **Flutter**, and **Traccar**.

The project demonstrates how to build a scalable enterprise fleet management platform using modern Laravel architecture, automated testing, clean code principles, and API-first development.

FleetTrack is being developed as a production-quality portfolio project inspired by real-world logistics and transportation management systems.

---

# Technology Stack

## Backend

- Laravel 12
- PHP 8.5
- MySQL
- Redis
- Laravel Sail (Docker)
- Pest
- PHPUnit

## Frontend

- Vue.js
- Vite

## Mobile

- Flutter

## GPS Platform

- Traccar

---

# Project Goals

FleetTrack aims to provide a complete fleet management solution supporting multiple logistics companies.

Core goals include:

- Multi-company architecture
- Fleet management
- Driver management
- Vehicle management
- GPS device management
- Real-time vehicle tracking
- Trip management
- Geofencing
- Alerts
- Reporting
- Mobile application
- REST API

---

# Architecture

FleetTrack follows an API-first architecture.

Every business module follows the same implementation pattern:

- Model
- Policy
- Form Requests
- Actions
- API Resource
- API Controller
- Feature Tests

Business logic is intentionally separated from controllers using Action classes, while authorization is handled through Laravel Policies.

Reusable components are extracted whenever a common pattern emerges.

Current reusable components include:

- BelongsToCompany trait
- visibleTo() tenant scope
- Company ownership architecture
- Shared testing traits

---

# Multi-Tenant Design

FleetTrack is designed as a multi-company platform.

Each company owns its own:

- Users
- Fleets
- Drivers
- Vehicles
- Devices
- Trips

Tenant isolation is enforced through:

- company_id ownership
- Laravel Policies
- Spatie Permission Teams
- Query scopes
- Business Actions

---

# Completed Modules

## Foundation

- Laravel project
- Docker environment
- Authentication
- Authorization
- Multi-company architecture
- Spatie Permission
- Spatie Teams
- Database seeders
- Database factories
- Automated testing foundation

## Company Management

Completed:

- Company model
- Company API foundation
- Authorization
- API Resources
- Feature tests

## Fleet Management

Completed:

- Full CRUD API
- Policies
- Actions
- Form Requests
- API Resources
- Feature tests

## Driver Management

Completed:

- Full CRUD API
- Policies
- Actions
- Form Requests
- API Resources
- Feature tests

## Vehicle Management

Completed:

- Full CRUD API
- Policies
- Actions
- Form Requests
- API Resources
- Feature tests

---

# Development Principles

FleetTrack follows several architectural principles:

- API-first development
- Thin Controllers
- Action-based business logic
- Policy-based authorization
- Feature-test-first development
- Multi-tenant security
- Reusable components
- Incremental refactoring

Every module is implemented file-by-file, fully tested, and refactored before moving to the next module.

---

# Testing

FleetTrack uses:

- Pest
- Laravel Feature Tests

Current automated tests cover:

- Company module
- Fleet module
- Driver module
- Vehicle module
- Authorization
- Validation
- Multi-tenant isolation
- CRUD operations

Every completed module includes a complete feature test suite.

---

# Current Roadmap

Completed:

- Foundation
- Company
- Fleet
- Driver
- Vehicle

Upcoming:

- GPS Device Management
- Traccar Integration
- Live Tracking
- Trip Management
- Geofencing
- Alerts
- Dashboard
- Reports
- Flutter Mobile Application

---

# Local Development

Clone the repository:

```bash
git clone <repository-url>

cd FleetTrack
```

Start Laravel Sail:

```bash
./vendor/bin/sail up -d
```

Install dependencies:

```bash
./vendor/bin/sail composer install
```

Generate the application key:

```bash
./vendor/bin/sail artisan key:generate
```

Run migrations and seeders:

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

Run the test suite:

```bash
./vendor/bin/sail artisan test
```

Run Laravel Pint:

```bash
./vendor/bin/sail pint
```

---

# Documentation

Project documentation includes:

- README.md
- FEATURES.md
- ARCHITECTURE.md
- docs/architecture-decisions.md
- docs/module-development-checklist.md

---

# License

FleetTrack is an educational and portfolio project created to demonstrate modern Laravel architecture, multi-tenant application design, automated testing, and clean software engineering practices.