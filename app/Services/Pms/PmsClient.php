<?php

namespace App\Services\Pms;

use Illuminate\Support\Facades\Http;

class PmsClient
{
    private function throttle(): void
    {
        usleep((int) (1_000_000 / config('pms.rate_limit_per_second')));
    }

    public function getUpdatedBookingIds(?string $updatedAfter): array
    {
        $this->throttle();

        return Http::pms()
            ->get('/api/bookings', array_filter(['updated_at.gt' => $updatedAfter], fn ($v) => $v !== null))
            ->throw()
            ->json('data');
    }

    public function getBooking(int $id): array
    {
        $this->throttle();

        return Http::pms()
            ->get("/api/bookings/{$id}")
            ->throw()
            ->json();
    }

    public function getGuest(int $id): array
    {
        $this->throttle();

        return Http::pms()
            ->get("/api/guests/{$id}")
            ->throw()
            ->json();
    }

    public function getRoom(int $id): array
    {
        $this->throttle();

        return Http::pms()
            ->get("/api/rooms/{$id}")
            ->throw()
            ->json();
    }

    public function getRoomType(int $id): array
    {
        $this->throttle();

        return Http::pms()
            ->get("/api/room-types/{$id}")
            ->throw()
            ->json();
    }
}
