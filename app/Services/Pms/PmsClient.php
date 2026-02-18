<?php

namespace App\Services\Pms;

use Illuminate\Support\Facades\Http;

class PmsClient implements PmsClientInterface
{
    private float $lastRequestAt = 0;

    private function throttle(): void
    {
        $interval = 1_000_000 / config('pms.rate_limit_per_second');
        $elapsed = (microtime(true) - $this->lastRequestAt) * 1_000_000;
        $remaining = $interval - $elapsed;

        if ($remaining > 0) {
            usleep((int) $remaining);
        }

        $this->lastRequestAt = microtime(true);
    }

    /**
     * @return list<int>
     */
    public function getUpdatedBookingIds(?string $updatedAfter): array
    {
        $this->throttle();

        return Http::pms()
            ->get('/api/bookings', array_filter(['updated_at.gt' => $updatedAfter], fn ($v) => $v !== null))
            ->throw()
            ->json('data');
    }

    /**
     * @return array{id: int, room_id: int, room_type_id: int, guest_ids: list<int>, arrival_date: string, departure_date: string, status: string, notes: ?string}
     */
    public function getBooking(int $id): array
    {
        $this->throttle();

        return Http::pms()
            ->get("/api/bookings/{$id}")
            ->throw()
            ->json();
    }

    /**
     * @return array{id: int, first_name: string, last_name: string, email: ?string}
     */
    public function getGuest(int $id): array
    {
        $this->throttle();

        return Http::pms()
            ->get("/api/guests/{$id}")
            ->throw()
            ->json();
    }

    /**
     * @return array{id: int, number: string, floor: int}
     */
    public function getRoom(int $id): array
    {
        $this->throttle();

        return Http::pms()
            ->get("/api/rooms/{$id}")
            ->throw()
            ->json();
    }

    /**
     * @return array{id: int, name: string, description: ?string}
     */
    public function getRoomType(int $id): array
    {
        $this->throttle();

        return Http::pms()
            ->get("/api/room-types/{$id}")
            ->throw()
            ->json();
    }
}
