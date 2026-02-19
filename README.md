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
