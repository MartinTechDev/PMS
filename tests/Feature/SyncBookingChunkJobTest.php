<?php

use App\Jobs\SyncBookingChunk;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('processes a chunk of booking IDs', function () {
    Http::fake([
        '*/api/bookings/1' => Http::response([
            'id' => 1,
            'room_id' => 10,
            'room_type_id' => 5,
            'guest_ids' => [20],
            'arrival_date' => '2026-03-01',
            'departure_date' => '2026-03-05',
            'status' => 'confirmed',
            'notes' => null,
        ]),
        '*/api/guests/20' => Http::response([
            'id' => 20,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]),
        '*/api/rooms/10' => Http::response([
            'id' => 10,
            'number' => '101',
            'floor' => 1,
        ]),
        '*/api/room-types/5' => Http::response([
            'id' => 5,
            'name' => 'Deluxe',
            'description' => null,
        ]),
    ]);

    $job = new SyncBookingChunk([1]);
    app()->call([$job, 'handle']);

    expect(Booking::where('external_id', 1)->exists())->toBeTrue();
    expect(Guest::where('external_id', 20)->exists())->toBeTrue();
    expect(Room::where('external_id', 10)->exists())->toBeTrue();
    expect(RoomType::where('external_id', 5)->exists())->toBeTrue();
});

it('uses the database queue connection', function () {
    $job = new SyncBookingChunk([1, 2, 3]);

    expect($job->connection)->toBe('database');
});

it('continues processing remaining IDs when one fails', function () {
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
    ]);

    $job = new SyncBookingChunk([1, 2]);
    app()->call([$job, 'handle']);

    expect(Booking::where('external_id', 1)->exists())->toBeFalse();
    expect(Booking::where('external_id', 2)->exists())->toBeTrue();
});
