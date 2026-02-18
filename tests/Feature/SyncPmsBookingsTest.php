<?php

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('syncs bookings from the PMS API', function () {
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
            'description' => 'Spacious deluxe room',
        ]),
        '*/api/bookings*' => Http::response(['data' => [1]]),
    ]);

    $this->artisan('pms:sync-bookings')->assertSuccessful();

    expect(RoomType::where('external_id', 5)->exists())->toBeTrue();
    expect(Room::where('external_id', 10)->exists())->toBeTrue();
    expect(Guest::where('external_id', 20)->exists())->toBeTrue();

    $booking = Booking::where('external_id', 1)->first();
    expect($booking)->not->toBeNull();
    expect($booking->guests()->where('external_id', 20)->exists())->toBeTrue();
});

it('is idempotent when run twice', function () {
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
        '*/api/bookings*' => Http::response(['data' => [1]]),
    ]);

    $this->artisan('pms:sync-bookings')->assertSuccessful();
    $this->artisan('pms:sync-bookings')->assertSuccessful();

    expect(Booking::count())->toBe(1);
    expect(Guest::count())->toBe(1);
    expect(Room::count())->toBe(1);
    expect(RoomType::count())->toBe(1);
});

it('continues syncing other bookings when one fails', function () {
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

    $this->artisan('pms:sync-bookings')->assertFailed();

    expect(Booking::count())->toBe(1);
    expect(Booking::where('external_id', 2)->exists())->toBeTrue();
});
