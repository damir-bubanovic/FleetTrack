# FleetTrack --- Features Overview

## Goal

FleetTrack is a modern fleet management and GPS tracking platform
designed for logistics companies to monitor vehicles, drivers, trips,
fleets, and live GPS locations through a centralized dashboard and
mobile application.

Built using:

-   Laravel
-   Vue.js
-   Flutter
-   Traccar
-   MySQL
-   Redis
-   Docker (Laravel Sail)

------------------------------------------------------------------------

# Current Development Phase

FleetTrack has successfully completed its foundational architecture and
the Fleet Management module.

Current focus:

-   Driver Management
-   Vehicle Management
-   Preparing Traccar integration

Fleet Management now serves as the reference implementation for future
modules, establishing the project's architecture for Actions, Policies,
Form Requests, API Resources, and feature testing.

------------------------------------------------------------------------

# Chapter 1: Authentication & User Management 🟢

## Completed

-   Multi-company user architecture
-   User authentication foundation
-   Spatie Laravel Permission integration
-   Spatie Teams integration using `company_id`
-   Role-based authorization
-   Permission-based authorization
-   User ↔ Company relationship
-   Internal system company architecture
-   Super Admin role
-   Company Admin role
-   Fleet Manager role
-   Driver role
-   Role and permission seeders
-   Company-scoped role assignments
-   Authentication test helpers
-   Feature testing foundation

------------------------------------------------------------------------

# Chapter 2: Company Management 🟢

## Foundation Completed

### Completed

-   Company model and database structure
-   Company API foundation
-   Company authorization
-   Company API Resources
-   Company Policies
-   Company Form Requests
-   Company feature testing foundation
-   System company exclusion
-   Centralized FleetTrack configuration

### Remaining Work

-   Complete Company CRUD
-   Company settings
-   Company logo
-   Contact information
-   Additional feature tests

------------------------------------------------------------------------

# Chapter 3: Fleet Management 🟢

## Completed

-   Fleet CRUD API
-   Fleet Actions
-   Fleet Policies
-   Fleet Form Requests
-   Fleet API Resources
-   Tenant isolation
-   Company ownership enforcement
-   Soft deletes
-   Reusable `BelongsToCompany` trait
-   11 passing Fleet feature tests

Fleet Management serves as the reference implementation for future
modules.

------------------------------------------------------------------------

# Chapter 4: Driver Management 🟡

## Current Milestone

-   Driver CRUD
-   Driver profile
-   Fleet assignment
-   License information
-   Driver status
-   Feature tests

------------------------------------------------------------------------

# Architecture

## Completed

-   Multi-company architecture
-   Thin API Controllers
-   Action-based business logic
-   Laravel Policies
-   Form Requests
-   API Resources
-   Reusable `BelongsToCompany` trait
-   ProvisionCompanyRoles service
-   Fleet module as reference implementation

------------------------------------------------------------------------

# Testing

## Completed

-   Pest testing infrastructure
-   Authentication helpers
-   Company helpers
-   Fleet helpers
-   Company feature tests
-   Fleet CRUD feature tests
-   Multi-tenant authorization tests
-   11 passing Fleet feature tests

------------------------------------------------------------------------

# Current Status

## Completed

-   Project foundation
-   Authentication
-   Authorization
-   Company Management foundation
-   Fleet Management module
-   Testing foundation
-   Documentation

## Current Milestone

-   Driver Management
