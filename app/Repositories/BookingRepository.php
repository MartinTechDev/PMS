<?php

namespace App\Repositories;

use App\Data\BookingData;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;

class BookingRepository
{
    public function upsert(BookingData $data, Room $room, Guest $guest): Booking
    {
        return Booking::updateOrCreate(
            ['external_id' => $data->external_id],
            [
                'room_id' => $room->id,
                'guest_id' => $guest->id,
                'check_in' => $data->check_in,
                'check_out' => $data->check_out,
                'status' => $data->status,
                'total_price' => $data->total_price,
            ],
        );
    }
}
