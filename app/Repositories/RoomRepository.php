<?php

namespace App\Repositories;

use App\Data\RoomData;
use App\Models\Room;

class RoomRepository
{
    public function upsert(RoomData $data): Room
    {
        return Room::updateOrCreate(
            ['external_id' => $data->external_id],
            ['name' => $data->name, 'floor' => $data->floor],
        );
    }
}
