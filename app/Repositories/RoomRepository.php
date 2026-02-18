<?php

namespace App\Repositories;

use App\Data\RoomData;
use App\Models\Room;
use App\Models\RoomType;

class RoomRepository
{
    public function upsert(RoomData $data, RoomType $roomType): Room
    {
        return Room::updateOrCreate(
            ['external_id' => $data->external_id],
            ['room_type_id' => $roomType->id, 'name' => $data->name],
        );
    }
}
