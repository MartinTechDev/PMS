<?php

use App\Repositories\BookingRepository;
use App\Repositories\GuestRepository;
use App\Repositories\RoomRepository;
use App\Repositories\RoomTypeRepository;
use App\Services\BookingSyncService;
use App\Services\Pms\PmsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns synced and failed counts', function () {
    $client = Mockery::mock(PmsClient::class);
    $client->shouldReceive('getUpdatedBookingIds')->once()->andReturn([42]);
    $client->shouldReceive('getBooking')->with(42)->once()->andReturn([
        'id' => 42,
        'room_id' => 1,
        'guest_id' => 1,
        'check_in' => '2026-03-01',
        'check_out' => '2026-03-05',
        'status' => 'confirmed',
        'total_price' => 300.00,
    ]);
    $client->shouldReceive('getGuest')->with(1)->once()->andReturn([
        'id' => 1,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => null,
        'phone' => null,
    ]);
    $client->shouldReceive('getRoom')->with(1)->once()->andReturn([
        'id' => 1,
        'room_type_id' => 1,
        'name' => 'Room 1',
    ]);
    $client->shouldReceive('getRoomType')->with(1)->once()->andReturn([
        'id' => 1,
        'name' => 'Standard',
    ]);

    $service = new BookingSyncService(
        $client,
        new BookingRepository,
        new GuestRepository,
        new RoomRepository,
        new RoomTypeRepository,
    );

    $result = $service->sync(null);

    expect($result['synced'])->toBe(1);
    expect($result['failed'])->toBe(0);
});

it('counts failures when client throws', function () {
    $client = Mockery::mock(PmsClient::class);
    $client->shouldReceive('getUpdatedBookingIds')->once()->andReturn([99]);
    $client->shouldReceive('getBooking')->with(99)->once()->andThrow(new RuntimeException('API error'));

    $service = new BookingSyncService(
        $client,
        new BookingRepository,
        new GuestRepository,
        new RoomRepository,
        new RoomTypeRepository,
    );

    $result = $service->sync(null);

    expect($result['synced'])->toBe(0);
    expect($result['failed'])->toBe(1);
});
