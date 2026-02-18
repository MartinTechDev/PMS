<?php

namespace App\Repositories;

use App\Data\BookingData;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;

class BookingRepository
{
    /**
     * @param  array<Guest>  $guests
     */
    public function upsert(BookingData $data, Room $room, RoomType $roomType, array $guests): Booking
    {
        $booking = Booking::updateOrCreate(
            ['external_id' => $data->external_id],
            [
                'room_id' => $room->id,
                'room_type_id' => $roomType->id,
                'check_in' => $data->check_in,
                'check_out' => $data->check_out,
                'status' => $data->status,
                'notes' => $data->notes,
            ],
        );

        $booking->guests()->sync(array_map(fn (Guest $g) => $g->id, $guests));

        return $booking;
    }
}
