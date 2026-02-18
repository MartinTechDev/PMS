<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class RoomData extends Data
{
    public function __construct(
        #[MapInputName('id')]
        public readonly int $external_id,
        public readonly int $room_type_id,
        public readonly string $name,
    ) {}
}
