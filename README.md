# FleetTrack

FleetTrack is a modern fleet management and GPS tracking platform built with **Laravel**, **Vue.js**, **Flutter**, and **Traccar**. The project demonstrates a scalable, multi-company architecture for managing companies, users, drivers, vehicles, GPS devices, trips, and real-time location tracking.

This application is being developed as a portfolio-quality project based on a real-world fleet management scenario. It emphasizes clean architecture, modern Laravel practices, API-first design, automated testing, and Docker-based development.

---

# Tech Stack

- Laravel 12
- PHP 8.5 (Laravel Sail)
- Vue.js
- Vite
- Flutter
- Traccar
- MySQL
- Redis
- Mailpit
- Docker (Laravel Sail)

---

# Goals

- Build a professional fleet management platform
- Support multiple logistics companies
- Implement real-time GPS tracking
- Manage companies, users, drivers, vehicles, and trips
- Integrate with Traccar
- Provide a responsive web dashboard
- Develop a cross-platform mobile application
- Follow Laravel best practices and clean architecture
- Maintain comprehensive automated tests

---

# Development Environment

- Ubuntu Linux
- Docker
- Laravel Sail
- Composer
- Node.js (NVM)
- Git & GitHub

---

# Architecture Highlights

- Multi-company architecture
- Company data isolation
- Spatie Laravel Permission with Teams
- `company_id` used as the team context
- Internal FleetTrack system company for Super Admin role assignments
- Versioned REST API (`/api/v1`)
- Laravel API Resources
- Form Requests
- Policies
- Actions and Services architecture
- PHP Enums
- Redis queues and caching
- Docker-based local development

---

# Current Status

FleetTrack is currently in active development.

## Completed

- Laravel project setup
- Docker (Laravel Sail)
- Multi-company database architecture
- Company model
- User model
- User ↔ Company relationship
- Authentication foundation
- Authorization foundation
- Spatie Permission integration
- Spatie Teams integration
- Role and permission architecture
- Company-scoped authorization
- Internal system company architecture
- Centralized FleetTrack configuration
- Database factories
- Database seeders
- Company API foundation
- Company API Resource
- Company feature testing foundation
- Project architecture documentation
- Passing test suite

## In Progress

- Company CRUD module

## Planned

- Driver Management
- Vehicle Management
- GPS Device Management
- Traccar Integration
- Fleet Dashboard
- Live GPS Tracking
- Trip Management
- Reports
- Mobile Application
- REST API expansion

---

# Running the Project

```bash
cp .env.example .env

./vendor/bin/sail up -d

./vendor/bin/sail composer install

./vendor/bin/sail artisan key:generate

./vendor/bin/sail artisan migrate:fresh --seed
```

Run the test suite:

```bash
./vendor/bin/sail artisan test
```

Check formatting:

```bash
./vendor/bin/sail pint --test
```

---

# Documentation

Project documentation includes:

- Architecture
- Features
- README
- API documentation (planned)
- Database diagram (planned)
- Deployment guide (planned)

---

# License

This project is intended for educational and portfolio purposes.
