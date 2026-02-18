<?php

namespace App\Data;

use App\Enums\BookingStatus;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class BookingData extends Data
{
    public function __construct(
        #[MapInputName('id')]
        public readonly int $external_id,
        public readonly int $room_id,
        public readonly int $room_type_id,
        #[MapInputName('guest_ids')]
        public readonly array $guest_ids,
        #[MapInputName('arrival_date')]
        public readonly string $check_in,
        #[MapInputName('departure_date')]
        public readonly string $check_out,
        public readonly BookingStatus $status,
        public readonly ?string $notes,
    ) {}
}
