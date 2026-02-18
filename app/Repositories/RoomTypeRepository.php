<?php

namespace App\Repositories;

use App\Data\RoomTypeData;
use App\Models\RoomType;

class RoomTypeRepository
{
    public function upsert(RoomTypeData $data): RoomType
    {
        return RoomType::updateOrCreate(
            ['external_id' => $data->external_id],
            ['name' => $data->name],
        );
    }
}
