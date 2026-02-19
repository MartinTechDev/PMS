<?php

use App\Jobs\SyncBookingChunk;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function fakePmsResponses(array $bookingIds = [1]): void
{
    Http::fake([
        '*/api/bookings/1' => Http::response([
            'id' => 1,
            'room_id' => 10,
            'room_type_id' => 5,
            'guest_ids' => [20],
            'arrival_date' => '2026-03-01',
            'departure_date' => '2026-03-05',
            'status' => 'confirmed',
            'notes' => 'VIP guest',
        ]),
        '*/api/bookings/2' => Http::response([
            'id' => 2,
            'room_id' => 11,
            'room_type_id' => 5,
            'guest_ids' => [21],
            'arrival_date' => '2026-04-01',
            'departure_date' => '2026-04-03',
            'status' => 'pending',
            'notes' => null,
        ]),
        '*/api/guests/20' => Http::response([
            'id' => 20,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]),
        '*/api/guests/21' => Http::response([
            'id' => 21,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => null,
        ]),
        '*/api/rooms/10' => Http::response([
            'id' => 10,
            'number' => '101',
            'floor' => 1,
        ]),
        '*/api/rooms/11' => Http::response([
            'id' => 11,
            'number' => '102',
            'floor' => 1,
        ]),
        '*/api/room-types/5' => Http::response([
            'id' => 5,
            'name' => 'Deluxe',
            'description' => 'Spacious deluxe room',
        ]),
        '*/api/bookings*' => Http::response(['data' => $bookingIds]),
    ]);
}

// ─── Synchronous (--sync) tests ──────────────────────────────

it('syncs bookings from the PMS API (--sync)', function () {
    fakePmsResponses([1]);

    $this->artisan('pms:sync-bookings', ['--sync' => true])->assertSuccessful();

    expect(RoomType::where('external_id', 5)->exists())->toBeTrue();
    expect(Room::where('external_id', 10)->exists())->toBeTrue();
    expect(Guest::where('external_id', 20)->exists())->toBeTrue();

    $booking = Booking::where('external_id', 1)->first();
    expect($booking)->not->toBeNull();
    expect($booking->guests()->where('external_id', 20)->exists())->toBeTrue();
});

it('is idempotent when run twice (--sync)', function () {
    fakePmsResponses([1]);

    $this->artisan('pms:sync-bookings', ['--sync' => true])->assertSuccessful();
    $this->artisan('pms:sync-bookings', ['--sync' => true])->assertSuccessful();

    expect(Booking::count())->toBe(1);
    expect(Guest::count())->toBe(1);
    expect(Room::count())->toBe(1);
    expect(RoomType::count())->toBe(1);
});

it('advances last_sync_at after a successful sync (--sync)', function () {
    fakePmsResponses([1]);

    expect(DB::table('pms_sync_states')->where('key', 'last_sync_at')->exists())->toBeFalse();

    $this->artisan('pms:sync-bookings', ['--sync' => true])->assertSuccessful();

    expect(DB::table('pms_sync_states')->where('key', 'last_sync_at')->exists())->toBeTrue();
});

it('does not advance last_sync_at when sync has failures (--sync)', function () {
    DB::table('pms_sync_states')->insert([
        'key' => 'last_sync_at',
        'value' => '2026-01-01T00:00:00+00:00',
        'updated_at' => now(),
    ]);

    Http::fake([
        '*/api/bookings/1' => Http::response([], 500),
        '*/api/bookings*' => Http::response(['data' => [1]]),
    ]);

    $this->artisan('pms:sync-bookings', ['--sync' => true])->assertFailed();

    expect(DB::table('pms_sync_states')->where('key', 'last_sync_at')->value('value'))
        ->toBe('2026-01-01T00:00:00+00:00');
});

it('handles an empty booking list (--sync)', function () {
    Http::fake([
        '*/api/bookings*' => Http::response(['data' => []]),
    ]);

    $this->artisan('pms:sync-bookings', ['--sync' => true])->assertSuccessful();

    expect(Booking::count())->toBe(0);
});

it('performs a full sync when --fresh flag is used (--sync)', function () {
    DB::table('pms_sync_states')->insert([
        'key' => 'last_sync_at',
        'value' => '2026-01-01T00:00:00+00:00',
        'updated_at' => now(),
    ]);

    Http::fake([
        '*/api/bookings*' => Http::response(['data' => []]),
    ]);

    $this->artisan('pms:sync-bookings', ['--fresh' => true, '--sync' => true])->assertSuccessful();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/api/bookings')
            && ! str_contains($request->url(), 'updated_at.gt');
    });
});

it('continues syncing other bookings when one fails (--sync)', function () {
    Http::fake([
        '*/api/bookings/1' => Http::response([], 500),
        '*/api/bookings/2' => Http::response([
            'id' => 2,
            'room_id' => 11,
            'room_type_id' => 5,
            'guest_ids' => [21],
            'arrival_date' => '2026-04-01',
            'departure_date' => '2026-04-03',
            'status' => 'pending',
            'notes' => null,
        ]),
        '*/api/guests/21' => Http::response([
            'id' => 21,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => null,
        ]),
        '*/api/rooms/11' => Http::response([
            'id' => 11,
            'number' => '102',
            'floor' => 1,
        ]),
        '*/api/room-types/5' => Http::response([
            'id' => 5,
            'name' => 'Deluxe',
            'description' => null,
        ]),
        '*/api/bookings*' => Http::response(['data' => [1, 2]]),
    ]);

    $this->artisan('pms:sync-bookings', ['--sync' => true])->assertFailed();

    expect(Booking::count())->toBe(1);
    expect(Booking::where('external_id', 2)->exists())->toBeTrue();
});

// ─── Async (default) dispatch tests ─────────────────────────

it('dispatches chunked jobs by default', function () {
    Queue::fake();

    Http::fake([
        '*/api/bookings*' => Http::response(['data' => [1, 2, 3, 4, 5]]),
    ]);

    $this->artisan('pms:sync-bookings', ['--chunk-size' => 2])
        ->assertSuccessful();

    Queue::assertPushed(SyncBookingChunk::class, 3);

    Queue::assertPushed(SyncBookingChunk::class, function (SyncBookingChunk $job) {
        return $job->bookingIds === [1, 2];
    });

    Queue::assertPushed(SyncBookingChunk::class, function (SyncBookingChunk $job) {
        return $job->bookingIds === [3, 4];
    });

    Queue::assertPushed(SyncBookingChunk::class, function (SyncBookingChunk $job) {
        return $job->bookingIds === [5];
    });
});

it('updates last_sync_at when dispatching jobs', function () {
    Queue::fake();

    Http::fake([
        '*/api/bookings*' => Http::response(['data' => [1]]),
    ]);

    expect(DB::table('pms_sync_states')->where('key', 'last_sync_at')->exists())->toBeFalse();

    $this->artisan('pms:sync-bookings')->assertSuccessful();

    expect(DB::table('pms_sync_states')->where('key', 'last_sync_at')->exists())->toBeTrue();
});

it('handles empty booking list in async mode', function () {
    Queue::fake();

    Http::fake([
        '*/api/bookings*' => Http::response(['data' => []]),
    ]);

    $this->artisan('pms:sync-bookings')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('dispatches a single job when bookings fit in one chunk', function () {
    Queue::fake();

    Http::fake([
        '*/api/bookings*' => Http::response(['data' => [1, 2, 3]]),
    ]);

    $this->artisan('pms:sync-bookings', ['--chunk-size' => 50])
        ->assertSuccessful();

    Queue::assertPushed(SyncBookingChunk::class, 1);

    Queue::assertPushed(SyncBookingChunk::class, function (SyncBookingChunk $job) {
        return $job->bookingIds === [1, 2, 3];
    });
});
