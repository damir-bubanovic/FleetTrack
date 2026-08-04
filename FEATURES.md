# FleetTrack Features

This document provides an overview of FleetTrack's implemented,
in-progress, and planned functionality.

------------------------------------------------------------------------

# Development Status

  Module                                   Status
  -------------------------------- -----------------------
  Foundation                             ✅ Complete
  Authentication & Authorization         ✅ Complete
  Company Management                     ✅ Complete
  Fleet Management                       ✅ Complete
  Driver Management                      ✅ Complete
  Vehicle Management                🚧 In Progress (Next)
  GPS Device Management                  ⏳ Planned
  Trip Management                        ⏳ Planned
  Geofencing                             ⏳ Planned
  Alerts                                 ⏳ Planned
  Reporting                              ⏳ Planned
  Flutter Mobile Application             ⏳ Planned

------------------------------------------------------------------------

# Foundation

## Completed

-   Laravel 12 project
-   Docker (Laravel Sail)
-   Multi-company architecture
-   Versioned REST API (`/api/v1`)
-   Redis integration
-   Database factories
-   Database seeders
-   Pest testing infrastructure
-   Architecture documentation

------------------------------------------------------------------------

# Authentication & Authorization

## Completed

-   Multi-company users
-   Spatie Laravel Permission
-   Spatie Teams using `company_id`
-   Super Admin role
-   Company Admin role
-   Fleet Manager role
-   Driver role
-   Company-scoped permissions
-   Authentication test helpers

------------------------------------------------------------------------

# Company Management

## Completed

-   Company model
-   Company API
-   Company authorization
-   API Resources
-   Policies
-   Seeders
-   Feature tests
-   Internal FleetTrack system company

## Planned

-   Company settings
-   Company logo
-   Contact profile

------------------------------------------------------------------------

# Fleet Management

## Completed

-   Fleet CRUD API
-   Create / Update / Delete Actions
-   Policies
-   Form Requests
-   API Resources
-   Soft Deletes
-   Multi-company isolation
-   Feature test suite

------------------------------------------------------------------------

# Driver Management

## Completed

-   Driver CRUD API
-   Fleet assignment
-   Driver status
-   License management
-   Create / Update / Delete Actions
-   Policies
-   Form Requests
-   API Resources
-   Feature test suite

------------------------------------------------------------------------

# Vehicle Management

## Planned Next

-   Vehicle CRUD
-   Fleet assignment
-   Registration details
-   VIN
-   Fuel type
-   Odometer
-   Vehicle status
-   Feature tests

------------------------------------------------------------------------

# GPS Device Management

## Planned

-   Device registration
-   Vehicle assignment
-   Traccar synchronization
-   Connectivity status

------------------------------------------------------------------------

# Trip Management

## Planned

-   Automatic trip detection
-   Driver assignment
-   Distance tracking
-   Trip history
-   Route information

------------------------------------------------------------------------

# Technical Features

-   Thin Controllers
-   Action-based business logic
-   Laravel Policies
-   Form Requests
-   API Resources
-   `BelongsToCompany` reusable trait
-   `visibleTo()` and `forCompany()` model scopes
-   PHP Enums
-   Automated feature testing

------------------------------------------------------------------------

# Testing

Current automated coverage includes:

-   Company module
-   Fleet module
-   Driver module
-   Multi-company authorization
-   CRUD operations
-   Validation rules
-   Business rules

All new modules follow the same implementation workflow:

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

# Long-Term Roadmap

-   Vehicle Management
-   GPS Device Management
-   Traccar Integration
-   Live Tracking
-   Dashboard
-   Reports
-   Flutter Mobile Application
