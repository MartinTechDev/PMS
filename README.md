# PMS Booking Sync

Syncs bookings from an external PMS (Property Management System) API into a local MySQL database and provides Excel export.

## Setup

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
# Create a MySQL database matching DB_DATABASE in .env (default: pmsv1)
php artisan migrate
npm run build
```

Configure PMS API credentials in `.env`:

```
PMS_BASE_URL=https://api.pms.donatix.info
PMS_RATE_LIMIT=2
PMS_TIMEOUT=30
PMS_RETRY_TIMES=3
```

## Commands

### Sync bookings

```bash
php artisan pms:sync-bookings
php artisan pms:sync-bookings --fresh            # Full sync, ignore last timestamp
php artisan pms:sync-bookings --sync             # Run synchronously (no queue)
php artisan pms:sync-bookings --chunk-size=20    # Booking IDs per job (default: 10)
```

### Export bookings to Excel

```bash
php artisan pms:export-bookings
php artisan pms:export-bookings --status=confirmed
php artisan pms:export-bookings --from=2026-01-01 --to=2026-01-31
php artisan pms:export-bookings --output=custom-report.xlsx
```

### Queue & scheduler

```bash
php artisan queue:work        # Process queued sync jobs
php artisan schedule:work     # Run the scheduler (syncs bookings hourly)
```

### Development

```bash
composer run dev              # Runs server, queue, logs, and Vite concurrently
php artisan test              # Run test suite (Pest)
vendor/bin/pint --dirty       # Format changed files
```

## How it works

The `pms:sync-bookings` command fetches updated booking IDs from the PMS API, chunks them into queued jobs, and each job fetches full booking details and upserts them locally. The command runs hourly via the Laravel scheduler. Bookings can be exported to Excel with optional filters for status, date range, and output path.

### DB-first optimisation for static entities

Rooms and room types are static data that rarely change. After the first sync they are already stored in the database. On every subsequent run, `BookingSyncService` checks the local DB before calling the API:

- **Room / Room Type** → DB lookup first (`findByExternalId`). API is called only if the record does not exist yet.
- **Guest** → always fetched from the API (email and other details can change).
- **Booking** → always fetched from the API (that is the purpose of the sync).

The in-memory `$roomCache` / `$roomTypeCache` arrays are kept as-is to avoid redundant DB hits within a single job.

## Database snapshot (full sync — 10 000 bookings)

Results after running `php artisan pms:sync-bookings --sync --fresh` against the PMS API.

### Record counts

| Table | Rows |
|---|---|
| bookings | 10 000 |
| guests | 200 |
| rooms | 52 |
| room_types | 6 |
| booking_guest (pivot) | 20 140 |

### Integrity

- Bookings with NULL `room_id`: **0**
- Bookings with NULL `room_type_id`: **0**
- All foreign-key relations intact

### Rooms

52 rooms across 5 floors (10–12 rooms per floor).

### Room types

| Type | Bookings |
|---|---|
| Presidential Suite | 1 694 |
| Family Room | 1 689 |
| Standard Double | 1 687 |
| Standard Single | 1 671 |
| Executive Suite | 1 659 |
| Deluxe King | 1 600 |

### Booking statuses

| Status | Count |
|---|---|
| cancelled | 3 419 |
| pending | 3 386 |
| confirmed | 3 195 |

### Date range

- Earliest check-in: **2024-09-01**
- Latest check-out: **2025-09-27**

### Guests per booking

| Metric | Value |
|---|---|
| Average | 2.01 |
| Minimum | 1 |
| Maximum | 3 |
