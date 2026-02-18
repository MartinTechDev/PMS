<?php

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exports bookings to an xlsx file', function () {
    $roomType = RoomType::create([
        'external_id' => 1,
        'name' => 'Deluxe',
        'description' => 'Test',
    ]);

    $room = Room::create([
        'external_id' => 1,
        'name' => '101',
        'floor' => 1,
    ]);

    $guest = Guest::create([
        'external_id' => 1,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@test.com',
    ]);

    $booking = Booking::create([
        'external_id' => 1,
        'room_id' => $room->id,
        'room_type_id' => $roomType->id,
        'check_in' => '2025-01-01',
        'check_out' => '2025-01-03',
        'status' => 'confirmed',
        'notes' => 'Test booking',
    ]);

    $booking->guests()->attach($guest);

    $output = str_replace('\\', '/', storage_path('app/test-export.xlsx'));

    $this->artisan("pms:export-bookings --output={$output}")
        ->assertSuccessful();

    expect(file_exists($output))->toBeTrue();
    expect(filesize($output))->toBeGreaterThan(0);

    @unlink($output);
});

it('shows warning when no bookings match filters', function () {
    $this->artisan('pms:export-bookings --status=confirmed')
        ->expectsOutput('No bookings match the given filters.')
        ->assertSuccessful();
});
