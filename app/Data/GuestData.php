<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class GuestData extends Data
{
    public function __construct(
        #[MapInputName('id')]
        public readonly int $external_id,
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly ?string $email,
        public readonly ?string $phone,
    ) {}
}
