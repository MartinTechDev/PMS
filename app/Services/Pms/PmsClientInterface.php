<?php

namespace App\Services\Pms;

interface PmsClientInterface
{
    /** @return array<int> */
    public function getUpdatedBookingIds(?string $updatedAfter): array;

    /**
     * @return array{
     *     id: int,
     *     external_id: string,
     *     arrival_date: string,
     *     departure_date: string,
     *     room_id: int,
     *     room_type_id: int,
     *     guest_ids: array<int>,
     *     status: string,
     *     notes: ?string
     * }
     */
    public function getBooking(int $id): array;

    /**
     * @return array{
     *     id: int,
     *     first_name: string,
     *     last_name: string,
     *     email: ?string
     * }
     */
    public function getGuest(int $id): array;

    /**
     * @return array{
     *     id: int,
     *     number: string,
     *     floor: int
     * }
     */
    public function getRoom(int $id): array;

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     description: ?string
     * }
     */
    public function getRoomType(int $id): array;
}
