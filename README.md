# FleetTrack

> **Enterprise-inspired Fleet Management & GPS Tracking Platform built
> with Laravel, Vue.js, Flutter and Traccar**

FleetTrack is a portfolio-quality fleet management platform designed to
demonstrate modern Laravel architecture, multi-company (multi-tenant)
application design, clean code practices, API-first development,
automated testing, and real-world fleet operations.

The project models how logistics companies manage fleets, drivers,
vehicles, GPS devices and trips while maintaining strict tenant
isolation and a scalable architecture.

------------------------------------------------------------------------

# Why FleetTrack?

FleetTrack is more than a CRUD application.

The project was created to demonstrate:

-   Enterprise-inspired Laravel architecture
-   Multi-company data isolation
-   REST API-first development
-   Thin Controllers
-   Action-based business logic
-   Laravel Policies & Form Requests
-   Automated Feature Testing with Pest
-   Incremental refactoring
-   Docker-based development
-   Production-ready coding standards

------------------------------------------------------------------------

# Technology Stack

## Backend

-   Laravel 12
-   PHP 8.5
-   MySQL
-   Redis
-   Pest
-   Laravel Sail (Docker)

## Frontend

-   Vue.js
-   Vite

## Mobile

-   Flutter

## GPS Platform

-   Traccar

------------------------------------------------------------------------

# Architecture Overview

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

### Key Principles

-   API-first architecture
-   Multi-company security
-   Thin Controllers
-   Action-based business logic
-   Form Request validation
-   Laravel Policies
-   API Resources
-   PHP Enums
-   Reusable `BelongsToCompany` trait
-   `visibleTo()` / `forCompany()` model scopes
-   Feature-test-driven development

------------------------------------------------------------------------

# Current Project Status

  Module                            Status
  -------------------------------- ---------
  Project Foundation                  ✅
  Authentication & Authorization      ✅
  Company Management                  ✅
  Fleet Management                    ✅
  Driver Management                   ✅
  Vehicle Management                🚧 Next
  GPS Devices                         ⏳
  Trips                               ⏳
  Geofencing                          ⏳
  Alerts                              ⏳
  Reports                             ⏳
  Flutter Mobile App                  ⏳

------------------------------------------------------------------------

# Completed

-   Multi-company architecture
-   Authentication foundation
-   Authorization foundation
-   Company module
-   Fleet module
-   Driver module
-   REST API v1
-   Docker development environment
-   Architecture documentation
-   Automated Feature Tests
-   Refactored company visibility using model scopes

------------------------------------------------------------------------

# Testing

FleetTrack uses **Pest** for feature testing.

Current automated coverage includes:

-   Company module
-   Fleet CRUD
-   Driver CRUD
-   Multi-company authorization
-   Validation rules
-   Business rules

Run the test suite:

``` bash
./vendor/bin/sail artisan test
```

------------------------------------------------------------------------

# Running the Project

``` bash
cp .env.example .env

./vendor/bin/sail up -d

./vendor/bin/sail composer install

./vendor/bin/sail artisan key:generate

./vendor/bin/sail artisan migrate:fresh --seed
```

------------------------------------------------------------------------

# Project Structure

``` text
app/
├── Actions/
├── Enums/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Models/
│   └── Concerns/
├── Policies/
└── Providers/

database/
routes/
tests/
```

------------------------------------------------------------------------

# Documentation

-   README.md
-   FEATURES.md
-   ARCHITECTURE.md
-   ARCHITECTURE_DECISIONS.md

------------------------------------------------------------------------

# Development Workflow

Every module follows the same process:

1.  Migration
2.  Model
3.  Factory
4.  Seeder
5.  Policy
6.  Form Requests
7.  Actions
8.  API Resource
9.  Controller
10. Feature Tests
11. Refactor
12. Commit

------------------------------------------------------------------------

# Roadmap

Next milestones:

-   Vehicle Management
-   GPS Device Management
-   Geofencing
-   Trip Management
-   Alerts
-   Dashboard
-   Traccar Integration
-   Flutter Mobile Application

------------------------------------------------------------------------

# License

FleetTrack is an educational and portfolio project intended to
demonstrate modern software engineering practices using the Laravel
ecosystem.
