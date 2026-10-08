# Detailing Garage CRM API

Base URL: `http://localhost:8000/api/v1`

The React client should set:

```text
VITE_API_URL=http://localhost:8000/api/v1
```

Laravel enforces authentication, permissions, PIN checks, and every money calculation. Hiding a button in React does not grant access.

## Authentication

`POST /auth/login`

```json
{
  "email": "owner@detailinggarage.test",
  "password": "Password@123"
}
```

```json
{
  "success": true,
  "message": "Login successful",
  "token": "1|plain-text-token",
  "user": {
    "id": 1,
    "name": "Rajesh Patel",
    "email": "owner@detailinggarage.test",
    "role": "owner",
    "status": "active"
  }
}
```

Protected requests:

```http
Authorization: Bearer TOKEN
```

`POST /auth/logout` revokes the current token.

`GET /auth/me` returns the authenticated user.

Passwords, password hashes, tokens, PINs, and PIN hashes are never returned.

## Roles

| Role | Access |
| --- | --- |
| `owner` | Every permission |
| `manager` | Operational and financial permissions. Cannot manage settings permissions. |
| `staff` | `attendance.my`, `salary.my`, `profile.view`, `profile.update` |

Unauthorized requests return HTTP 403:

```json
{
  "success": false,
  "message": "Forbidden"
}
```

## Permissions

`dashboard.view`

`leads.view` `leads.create` `leads.update` `leads.delete`

`jobs.view` `jobs.create` `jobs.update` `jobs.delete`

`expenses.view` `expenses.create` `expenses.update` `expenses.delete`

`vendors.view` `vendors.create` `vendors.update` `vendors.delete`

`staff.view` `staff.create` `staff.update` `staff.delete`

`attendance.view` `attendance.manage` `attendance.my`

`salary.view` `salary.manage` `salary.my`

`extra_pay.view` `extra_pay.manage`

`reports.view`

`activity_logs.view`

`settings.view` `settings.manage`

`profile.view` `profile.update`

## Security PIN

Development PINs are stored only as hashes:

- Owner `123456`
- Manager `654321`

`POST /security/verify-pin`

```json
{ "pin": "123456" }
```

```json
{
  "success": true,
  "message": "PIN verified",
  "expires_at": "2026-10-06T12:15:00+05:30"
}
```

A verified PIN lasts 15 minutes (`PIN_SESSION_MINUTES`). After that, sensitive routes return HTTP 403:

```json
{
  "success": false,
  "code": "PIN_EXPIRED",
  "message": "Security PIN has expired"
}
```

Five failed attempts lock verification for 15 minutes. HTTP 423:

```json
{
  "success": false,
  "code": "PIN_LOCKED",
  "message": "Too many failed attempts. Try again later."
}
```

Missing verification on a sensitive route returns HTTP 403:

```json
{
  "success": false,
  "code": "PIN_REQUIRED",
  "message": "Security PIN verification required"
}
```

`GET /security/status` shows whether a PIN is configured and whether the current token is verified.

`POST /security/logout` ends the PIN session and leaves the API token in place.

`PUT /security/pin`

```json
{
  "current_pin": "123456",
  "new_pin": "987654",
  "new_pin_confirmation": "987654"
}
```

Changing the PIN clears the current verification. The new PIN is hashed. The plaintext PIN is never written to activity logs.

PIN-protected areas: dashboard, financial and staff reports, office expenses, personal expenses, expense categories, vendor payments, staff create/update/delete, salary management, extra pay, activity logs, business settings, and master-data mutations.

Staff salary at `GET /salary/my` does not require an owner PIN. Staff cannot open another salary record.

## Pagination

List endpoints default to 20 rows and allow at most 100 (`per_page`).

```json
{
  "success": true,
  "message": "Operation successful",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  }
}
```

Sort fields are whitelisted. Unknown sort columns are ignored.

## Leads

`GET /leads` `POST /leads` `GET /leads/{lead}` `PUT /leads/{lead}` `DELETE /leads/{lead}`

Filters: `search`, `status`, `platform`, `is_organic`, `service`, `car_condition`, `preferred_finish`, `campaign_id`, `adset_id`, `date_from`, `date_to`, `planned_date_from`, `planned_date_to`.

Search covers name, phone, email, car model, and campaign name.

Notes:

`GET|POST /leads/{lead}/notes`

`PUT|DELETE /leads/{lead}/notes/{note}`

Follow-ups:

`GET|POST /leads/{lead}/followups`

`PUT|DELETE /leads/{lead}/followups/{followup}`

Statuses: `pending`, `completed`, `cancelled`.

Convert:

`POST /leads/{lead}/convert-to-job`

```json
{
  "car_number": "GJ01AB1234",
  "income": 15000,
  "discount": 1000,
  "product_charge": 3500,
  "labour_charge": 2500
}
```

Customer name, phone, email, car, service, planned date, and source are copied from the lead. The lead status becomes Converted. The job and the lead update commit together.

## Jobs and payments

`GET|POST /jobs` `GET|PUT|DELETE /jobs/{job}`

Money sent by the client for profit, pending pay, final amount, or payment status is ignored. The API stores:

- Final amount = income − discount
- Profit = final amount − product charge − labour charge
- Pending pay = final amount − amount paid
- Payment status `paid`, `partial`, or `pending`

Job status: `booked`, `in_progress`, `completed`, `delivered`, `cancelled`.

`GET|POST /jobs/{job}/payments`

`PUT|DELETE /jobs/{job}/payments/{payment}`

```json
{
  "payment_date": "2026-10-06",
  "amount": 10000,
  "payment_method": "upi"
}
```

Methods: `cash`, `upi`, `card`, `bank_transfer`, `other`.

Each payment recalculates `amount_paid`, `pending_pay`, and `payment_status`.

## Expenses and vendors

Office expenses, personal expenses, and expense categories require a verified PIN.

`GET|POST /office-expenses` `GET|PUT|DELETE /office-expenses/{id}`

Filters: `search`, `category`, `date_from`, `date_to`.

`GET|POST /personal-expenses` `GET|PUT|DELETE /personal-expenses/{id}`

`staff_id` must reference a staff record.

`GET|POST /expense-categories` `PUT|DELETE /expense-categories/{id}`

A category that is already used is deactivated instead of removed.

Vendors:

`GET|POST /vendors` `GET|PUT|DELETE /vendors/{vendor}`

Vendor payments require a PIN and a `vendor_id`:

`GET|POST /vendor-payments` `GET|PUT|DELETE /vendor-payments/{payment}`

## Staff and master data

`GET|POST /staff` `GET|PUT|DELETE /staff/{staff}`

Creating or changing staff, including salary, requires a PIN. Listing staff does not include salary figures until the PIN is verified.

`GET|POST /staff-categories` `GET|PUT|DELETE /staff-categories/{id}`

`GET|POST /work-types` `GET|PUT|DELETE /work-types/{id}`

`GET|POST /lead-statuses` `GET|PUT|DELETE /lead-statuses/{id}`

Mutations of these catalogs require `settings.manage` and a verified PIN. Reads of work types, lead statuses, and staff categories stay available for daily operations.

## Attendance

`POST /attendance/clock-in`

`POST /attendance/clock-out`

Staff can clock only themselves. The server clock is used. A second open clock-in is rejected. Clock-out sets `total_minutes` from the two timestamps.

`GET /attendance/my` returns the authenticated staff member's history.

Owner and manager routes, according to permission:

`GET /attendance` `GET /attendance/today` `GET /attendance/monthly` `GET /attendance/{id}`

`POST /attendance` `PUT /attendance/{id}` `DELETE /attendance/{id}`

## Salary and extra pay

`GET /salary/my` is limited to the authenticated staff member. The API ignores any `staff_id` sent by that user.

`GET /salary/{salary}` for someone else's record returns HTTP 403.

Owner and manager management requires `salary.view` or `salary.manage` plus a verified PIN:

`GET /salary` `GET /salary/{salary}` `PUT /salary/{salary}`

`POST /salary/generate`

```json
{ "year": 2026, "month": 10 }
```

Active and on-leave staff receive one record per month. Extra pay for that month is included.

Final payable = basic salary + extra pay + bonus − deduction − advance.

Paid records are not regenerated. A second generate call updates the existing unpaid record instead of inserting a duplicate.

`POST /salary/{salary}/mark-paid`

Extra pay (`extra_pay.view` / `extra_pay.manage` plus PIN):

`GET|POST /extra-pay` `GET|PUT|DELETE /extra-pay/{id}`

Reasons: Overtime, Extra Work, Incentive, Bonus, Holiday Work, Performance Bonus, Other.

## Reports

`GET /reports/financial` requires `reports.view` and a verified PIN.

Returns revenue, product costs, labour costs, office expenses, personal expenses, vendor payments, staff salary, extra pay, gross profit, net profit, and pending customer payments.

`GET /reports/leads` requires `reports.view`.

Returns totals, conversion rate, and breakdowns by status, platform, service, and campaign.

`GET /reports/staff` requires `reports.view` and a verified PIN.

Returns staff, attendance, working hours, salary, and extra pay.

Optional `date_from` and `date_to` limit the financial and lead reports.

## Dashboard

`GET /dashboard` requires `dashboard.view` and a verified PIN.

Lead, job, financial, and attendance totals are included, plus chart series for revenue, expenses, profit, leads, jobs, and attendance. Financial numbers are not returned when the PIN is missing.

## Activity logs

`GET /activity-logs` `GET /activity-logs/{id}`

Requires `activity_logs.view` and a verified PIN. Staff always receive HTTP 403.

Filters: `user_id`, `role`, `action`, `module`, `entity_type`, `entity_id`, `date_from`, `date_to`, `search`.

Creates store `new_values` only. Updates store the previous and next values. Deletes store `old_values` only.

## Settings

`GET /settings` requires `settings.view` and a verified PIN.

`PUT /settings` requires `settings.manage` and a verified PIN.

Fields: `business_name`, `logo`, `phone`, `email`, `address`, `currency`, `currency_symbol`, `timezone`.

## Profile

`GET /profile` `PUT /profile` `PUT /profile/password`

Staff may update their allowed profile fields and password.

## Errors

Validation, HTTP 422:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

Unauthenticated requests return HTTP 401. Missing records return HTTP 404:

```json
{
  "success": false,
  "message": "Resource not found"
}
```

Money fields use `decimal(15,2)`. Send numbers greater than or equal to zero. Do not send floats that the client has already rounded into profit or payroll totals.
