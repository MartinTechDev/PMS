<?php

namespace App\Repositories;

use App\Data\GuestData;
use App\Models\Guest;

class GuestRepository
{
    public function upsert(GuestData $data): Guest
    {
        return Guest::updateOrCreate(
            ['external_id' => $data->external_id],
            [
                'first_name' => $data->first_name,
                'last_name' => $data->last_name,
                'email' => $data->email,
                'phone' => $data->phone,
            ],
        );
    }
}
