<?php

use App\Services\Pms\PmsClient;
use Illuminate\Support\Facades\Http;

it('fetches updated booking ids without a filter when updatedAfter is null', function () {
    Http::fake([
        '*/api/bookings*' => Http::response(['data' => [1, 2, 3]]),
    ]);

    $ids = (new PmsClient)->getUpdatedBookingIds(null);

    expect($ids)->toBe([1, 2, 3]);
    Http::assertSent(fn ($r) => str_contains($r->url(), '/api/bookings') && ! str_contains($r->url(), 'updated_at.gt'));
});

it('passes updated_at.gt filter when updatedAfter is provided', function () {
    Http::fake([
        '*/api/bookings*' => Http::response(['data' => [5]]),
    ]);

    $ids = (new PmsClient)->getUpdatedBookingIds('2026-01-01T00:00:00+00:00');

    expect($ids)->toBe([5]);
    Http::assertSent(fn ($r) => str_contains($r->url(), 'updated_at.gt='));
});

it('fetches a booking by id', function () {
    $payload = [
        'id' => 7,
        'room_id' => 1,
        'room_type_id' => 1,
        'guest_ids' => [1],
        'arrival_date' => '2026-03-01',
        'departure_date' => '2026-03-05',
        'status' => 'confirmed',
        'notes' => null,
    ];

    Http::fake(['*/api/bookings/7' => Http::response($payload)]);

    expect((new PmsClient)->getBooking(7))->toBe($payload);
});

it('fetches a guest by id', function () {
    $payload = ['id' => 20, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com'];

    Http::fake(['*/api/guests/20' => Http::response($payload)]);

    expect((new PmsClient)->getGuest(20))->toBe($payload);
});

it('fetches a room by id', function () {
    $payload = ['id' => 10, 'number' => '101', 'floor' => 1];

    Http::fake(['*/api/rooms/10' => Http::response($payload)]);

    expect((new PmsClient)->getRoom(10))->toBe($payload);
});

it('fetches a room type by id', function () {
    $payload = ['id' => 5, 'name' => 'Deluxe', 'description' => null];

    Http::fake(['*/api/room-types/5' => Http::response($payload)]);

    expect((new PmsClient)->getRoomType(5))->toBe($payload);
});
