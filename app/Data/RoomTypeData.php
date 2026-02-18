<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class RoomTypeData extends Data
{
    public function __construct(
        #[MapInputName('id')]
        public readonly int $external_id,
        public readonly string $name,
        public readonly ?string $description,
    ) {}
}
