<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class BookingData extends Data
{
    public function __construct(
        #[MapInputName('id')]
        public readonly int $external_id,
        public readonly int $room_id,
        public readonly int $guest_id,
        public readonly string $check_in,
        public readonly string $check_out,
        public readonly string $status,
        public readonly ?float $total_price,
    ) {}
}
