# Detailing Garage CRM API

Laravel REST API for the Detailing Garage CRM. The React application is the client. This API is the authority for authentication, permissions, security PINs, financial calculations, and CRM records.

## Requirements

- PHP 8.2+
- Composer
- MySQL
- The `bcmath` PHP extension

## Setup

```bash
composer install

cp .env.example .env

php artisan key:generate

php artisan migrate:fresh --seed

php artisan storage:link

php artisan optimize:clear

php artisan serve
```

Create the MySQL database `detailing_garage` before migrating. Default connection values are in `.env.example`.

## Testing

```bash
php artisan test
```

## API

Base URL:

```text
http://localhost:8000/api/v1
```

React environment variable:

```text
VITE_API_URL=http://localhost:8000/api/v1
```

Send the Sanctum token on protected requests:

```text
Authorization: Bearer TOKEN
```

Development login (local seed data only):

- Owner: `owner@detailinggarage.test` / `Password@123` / PIN `123456`
- Manager: `manager@detailinggarage.test` / `Password@123` / PIN `654321`
- Staff: `staff1@detailinggarage.test` through `staff8@detailinggarage.test` / `Password@123`

Financial screens, salary management, expenses, vendor payments, activity logs, and settings require a verified PIN. Staff accounts can use attendance, their own salary, and their own profile only.

See `API_DOCUMENTATION.md` for endpoints, filters, and error responses.

## Scheduler

Mark staff who never clocked in as absent:

```bash
php artisan schedule:work
```

The command `attendance:mark-missing-absent` runs daily at 23:30 Asia/Kolkata.
