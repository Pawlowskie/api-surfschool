# Surf School API

## Overview

REST API for a surf school booking system. Admins manage courses and sessions, users reserve seats with confirmation and reminder flows. The focus is on consistency (capacity), security (roles + JWT), and automated workflows (emails + jobs).

## Live Demo

- API URL: <ADD_URL>
- Swagger UI: <ADD_URL>/api/docs
- Demo account: <ADD_EMAIL> / <ADD_PASSWORD>
- Screenshots: <ADD_FOLDER_OR_LINK>

## Key Features

- Course and session management with capacity enforcement.
- Booking lifecycle: `pending`, `confirmed`, `cancelled`.
- Email confirmation for pending bookings.
- Automated reminders 24h before session start.
- Auto-cancel pending bookings 12h before session.
- JWT auth with refresh token rotation.
- Self-service profile actions: change email, change password.

## Domain Rules

- `pending` and `confirmed` bookings hold a seat.
- `cancelled` releases the seat.
- Capacity cannot drop below booked seats.
- Reminders go out 24h before start.
- Pending bookings cancel 12h before start if not confirmed.

## Access Control

- Public: GET courses and sessions.
- Authenticated users: read their own bookings.
- Authenticated users: change email and password (email change triggers re-verification).
- Admins: full CRUD on courses, sessions, bookings.

## Auth Flow (JWT + Refresh Tokens)

- Login returns `token` + `refresh_token`.
- Refresh rotates the refresh token (single-use).
- Changing email invalidates refresh tokens and requires re-verification.

## API Highlights

- Auth: `POST /api/login_check`, `POST /api/token/refresh`
- Profile: `GET /api/me`, `PATCH /api/me/email`, `PATCH /api/me/password`
- Bookings: `POST /api/bookings`, `GET /api/bookings/{id}`, `PATCH /api/bookings/{id}`
- Admin: courses/sessions CRUD

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

Confirm a booking:

```bash
curl -X GET 'http://localhost:8000/api/bookings/confirm/<token>'
```

Change email (re-verification required):

```bash
curl -X PATCH 'http://localhost:8000/api/me/email' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer <JWT>' \
  -d '{"email":"new@email.com"}'
```

Change password (requires current password):

```bash
curl -X PATCH 'http://localhost:8000/api/me/password' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer <JWT>' \
  -d '{"currentPassword":"OLD_PASS","password":"NEW_PASS_123"}'
```

## Background Jobs

- Send booking reminders: `php bin/console app:send-booking-reminders`
- Cancel expired bookings: `php bin/console app:cancel-expired-bookings`
- Clear expired refresh tokens (nightly at 03:00): `php bin/console gesdinet:jwt:clear`

Scheduler worker:

```bash
php bin/console messenger:consume scheduler_default -vv
```

## Local Setup

```bash
composer install
php bin/console doctrine:migrations:migrate
symfony server:start
```

Environment variables:

- `DATABASE_URL`
- `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `JWT_PASSPHRASE`
- `MAILER_DSN`
- `MESSENGER_TRANSPORT_DSN`

Workers (async emails):

```bash
php bin/console messenger:consume async -vv
```

## Tests

```bash
APP_ENV=test php vendor/bin/phpunit
```

Notes:

- Test DB uses `var/test.db` (SQLite).
- Mailer uses `null://null` in tests.
- JWT test keys live in `config/jwt/`.
