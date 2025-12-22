# Surf School API

## Overview

REST API for a surf school booking system. It lets admins create courses and schedule sessions, and lets users reserve seats with confirmation and reminder flows. The goal is to keep capacity consistent, enforce permissions, and automate time-based actions (reminders and cancellations).

## Why This Project

- Practice real booking constraints (capacity, timing, confirmations).
- Show clean API design with roles and automated jobs.

## Key Features

- Course and session management with capacity and available seats.
- Booking lifecycle with statuses: `pending`, `confirmed`, `cancelled`.
- Email confirmation link for pending bookings.
- Automatic reminders 24h before session start.
- Automatic cancellation of unconfirmed bookings 12h before the session.
- Role-based access control (admin vs authenticated user vs public).

## Domain Rules

- `pending` and `confirmed` bookings hold a seat.
- `cancelled` releases the seat.
- Capacity can never be reduced below already booked seats.
- Reminders are sent 24h before start for both pending and confirmed bookings.
- Pending bookings are cancelled 12h before start if still not confirmed.

## Access Control

- Public: GET courses and sessions.
- Authenticated users: can read their own bookings.
- Admins: full CRUD on courses, sessions, and bookings.

## Tech Stack

- Symfony 7 + API Platform
- Doctrine ORM (SQLite in tests)
- LexikJWTAuthenticationBundle (JWT auth)
- Messenger + Mailer (async email)
- PHPUnit (unit + integration tests)

## Architecture Notes

- `BookingManager` and `SessionManager` centralize seat logic.
- Doctrine subscribers keep seat counts consistent on create/update/delete.
- Business exceptions are mapped to API errors.
- Booking status uses a PHP Enum for type safety.
- Session uses optimistic locking (`version`) to protect seat updates.

## Roadmap

- Add payment flow (Stripe) before confirmation.
- Add user self-service (resend confirmation email, update profile).
- Add admin dashboard stats (occupancy, cancellations).

## Booking Status Values

The `status` field accepts one of:

- `pending`
- `confirmed`
- `cancelled`

## API Examples

Create a booking:

```bash
curl -X POST 'http://localhost:8000/api/bookings' \
  -H 'accept: application/ld+json' \
  -H 'Content-Type: application/ld+json' \
  -H 'Authorization: Bearer <JWT>' \
  -d '{
    "firstName": "Toto",
    "lastName": "Toto",
    "email": "toto@toto.com",
    "phone": "0600000000",
    "age": 18,
    "status": "pending",
    "session": "/api/sessions/1"
  }'
```

Update a booking status:

```bash
curl -X PATCH 'http://localhost:8000/api/bookings/1' \
  -H 'accept: application/ld+json' \
  -H 'Content-Type: application/merge-patch+json' \
  -H 'Authorization: Bearer <JWT>' \
  -d '{
    "status": "confirmed"
  }'
```

Confirm a booking from the email link:

```bash
curl -X GET 'http://localhost:8000/api/bookings/confirm/<token>'
```

## Background Jobs (CLI)

Send booking reminders (24h before session):

```bash
php bin/console app:send-booking-reminders
```

Auto-cancel pending bookings 12h before session (and notify by email):

```bash
php bin/console app:cancel-expired-bookings
```

## Tests (PHPUnit)

Run all tests (use your local PHP binary):

```bash
APP_ENV=test php vendor/bin/phpunit
```

Readable output (one line per test):

```bash
APP_ENV=test php vendor/bin/phpunit --testdox
```

Run a single test file:

```bash
APP_ENV=test php vendor/bin/phpunit tests/Integration/BookingPermissionsTest.php
```

Notes:

- Test DB uses `var/test.db` (SQLite).
- Mailer uses `null://null` in tests (no real emails).
- JWT uses test keys from `config/jwt/test_private.pem` and `config/jwt/test_public.pem`.
