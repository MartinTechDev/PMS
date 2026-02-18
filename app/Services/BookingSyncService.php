<?php

namespace App\Services;

use App\Data\BookingData;
use App\Data\GuestData;
use App\Data\RoomData;
use App\Data\RoomTypeData;
use App\Repositories\BookingRepository;
use App\Repositories\GuestRepository;
use App\Repositories\RoomRepository;
use App\Repositories\RoomTypeRepository;
use App\Services\Pms\PmsClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BookingSyncService
{
    public function __construct(
        private readonly PmsClient $client,
        private readonly BookingRepository $bookingRepository,
        private readonly GuestRepository $guestRepository,
        private readonly RoomRepository $roomRepository,
        private readonly RoomTypeRepository $roomTypeRepository,
    ) {}

    /**
     * Sync all bookings updated after the given timestamp.
     *
     * @return array{synced: int, failed: int}
     */
    public function sync(?string $updatedAfter): array
    {
        $ids = $this->client->getUpdatedBookingIds($updatedAfter);

        $synced = 0;
        $failed = 0;

        foreach ($ids as $id) {
            try {
                DB::transaction(function () use ($id) {
                    $this->syncBooking((int) $id);
                });
                $synced++;
            } catch (Throwable $e) {
                $failed++;
                Log::channel('pms_errors')->error('Failed to sync booking', [
                    'booking_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return compact('synced', 'failed');
    }

    private function syncBooking(int $id): void
    {
        $bookingData = BookingData::from($this->client->getBooking($id));

        $guests = [];
        foreach ($bookingData->guest_ids as $guestId) {
            $guestData = GuestData::from($this->client->getGuest($guestId));
            $guests[] = $this->guestRepository->upsert($guestData);
        }

        $roomData = RoomData::from($this->client->getRoom($bookingData->room_id));
        $room = $this->roomRepository->upsert($roomData);

        $roomTypeData = RoomTypeData::from($this->client->getRoomType($bookingData->room_type_id));
        $roomType = $this->roomTypeRepository->upsert($roomTypeData);

        $this->bookingRepository->upsert($bookingData, $room, $roomType, $guests);
    }
}
