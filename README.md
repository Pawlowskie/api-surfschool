# Surf School API

## Booking status values

The `status` field accepts one of:

- `pending`
- `confirmed`
- `cancelled`

## Example requests

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
