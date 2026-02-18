# PMS Booking Synchronization -- Complete Implementation Specification

Generated at: 2026-02-18T19:37:16.859429 UTC

------------------------------------------------------------------------

# 1. ARCHITECTURE & DESIGN

## 1.1 Goal

Synchronize bookings from:

https://api.pms.donatix.info

Requirements:

-   Fetch bookings (filtered by updated_at.gt)
-   Fetch related rooms, room types, guests
-   Store/update locally
-   Respect 2 requests/sec rate limit
-   Be idempotent
-   Provide CLI progress feedback
-   Use repository pattern
-   Use DTO layer (Spatie Laravel Data)
-   Use dedicated logging channels
-   Use config file for environment values
-   Use HTTP Macro
-   Use database transactions
-   Include basic unit tests
-   Use separate test database (sqlite in-memory)

No overengineering. No queue bus chains.

------------------------------------------------------------------------

## 1.2 High-Level Flow

Artisan Command ↓ BookingSyncService ↓ PmsClient (HTTP Macro + Rate
Limiting) ↓ Repositories ↓ Database

------------------------------------------------------------------------

## 1.3 Responsibilities

### Command

-   Retrieve last_sync_at
-   Call service
-   Show progress bar
-   Update sync state

### BookingSyncService

-   Orchestrates synchronization
-   Wraps each booking sync in DB transaction
-   Logs errors
-   Continues on failure

### PmsClient

-   Calls PMS API
-   Applies rate limiting
-   Uses Http::pms() macro
-   Throws exceptions on failure

### Repositories

-   Encapsulate database persistence
-   Use updateOrCreate
-   No business logic

### DTO Layer

-   Convert API arrays into structured objects
-   Prevent raw array usage in service layer

------------------------------------------------------------------------

## 1.4 Why No Queue Bus Chains?

Not required because:

-   API rate limit = 2/sec
-   Sync is pull-based
-   No heavy transformation
-   Incremental sync reduces load
-   Simpler operational model
-   Lower complexity

------------------------------------------------------------------------

# 2. IMPLEMENTATION DETAILS

------------------------------------------------------------------------

## 2.1 Folder Structure

app/ ├── Console/Commands/SyncPmsBookingsCommand.php ├── Services/ │ ├──
BookingSyncService.php │ └── Pms/PmsClient.php ├── Repositories/ │ ├──
BookingRepository.php │ ├── GuestRepository.php │ ├── RoomRepository.php
│ └── RoomTypeRepository.php ├── Data/ │ ├── BookingData.php │ ├──
GuestData.php │ ├── RoomData.php │ └── RoomTypeData.php ├──
Providers/PmsServiceProvider.php

------------------------------------------------------------------------

## 2.2 Configuration

### config/pms.php

``` php
return [
    'base_url' => env('PMS_BASE_URL'),
    'rate_limit_per_second' => env('PMS_RATE_LIMIT', 2),
    'timeout' => env('PMS_TIMEOUT', 10),
    'retry_times' => env('PMS_RETRY_TIMES', 3),
];
```

### .env

    PMS_BASE_URL=https://api.pms.donatix.info
    PMS_RATE_LIMIT=2
    PMS_TIMEOUT=10
    PMS_RETRY_TIMES=3

Never call env() outside config.

------------------------------------------------------------------------

## 2.3 HTTP Macro

Location: App`\Providers`{=tex}`\PmsServiceProvider`{=tex}

``` php
use Illuminate\Support\Facades\Http;

public function boot(): void
{
    Http::macro('pms', function () {
        return Http::baseUrl(config('pms.base_url'))
            ->timeout(config('pms.timeout'))
            ->retry(config('pms.retry_times'), 200);
    });
}
```

Usage:

``` php
Http::pms()->get('/api/bookings');
```

------------------------------------------------------------------------

## 2.4 Rate Limiting (2 Requests per Second)

Inside PmsClient:

``` php
private function throttle(): void
{
    usleep(1000000 / config('pms.rate_limit_per_second'));
}
```

Call throttle() before every request.

------------------------------------------------------------------------

## 2.5 PmsClient Example

``` php
class PmsClient
{
    private function throttle(): void
    {
        usleep(1000000 / config('pms.rate_limit_per_second'));
    }

    public function getUpdatedBookingIds(?string $updatedAfter): array
    {
        $this->throttle();

        return Http::pms()
            ->get('/api/bookings', [
                'updated_at.gt' => $updatedAfter,
            ])
            ->throw()
            ->json();
    }
}
```

------------------------------------------------------------------------

## 2.6 Repository Pattern

Example: BookingRepository

``` php
class BookingRepository
{
    public function upsert(array $data): Booking
    {
        return Booking::updateOrCreate(
            ['external_id' => $data['external_id']],
            $data
        );
    }
}
```

------------------------------------------------------------------------

## 2.7 Logging Configuration

In config/logging.php:

``` php
'pms_errors' => [
    'driver' => 'single',
    'path' => storage_path('logs/pms/errors.log'),
    'level' => 'error',
],
```

All PMS logs stored in:

storage/logs/pms/

------------------------------------------------------------------------

# 3. TESTING & DATABASE

------------------------------------------------------------------------

## 3.1 Separate Test Database

phpunit.xml:

    DB_CONNECTION=sqlite
    DB_DATABASE=:memory:

------------------------------------------------------------------------

## 3.2 Use RefreshDatabase

``` php
use Illuminate\Foundation\Testing\RefreshDatabase;
```

------------------------------------------------------------------------

## 3.3 HTTP Fake Example

``` php
Http::fake([
    '*/api/bookings*' => Http::response([1]),
]);
```

------------------------------------------------------------------------

## FINAL RESULT

Clean, SOLID, production-ready, idempotent, testable Laravel
implementation.
