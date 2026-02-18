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
use App\Services\Pms\PmsClientInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BookingSyncService
{
    private array $roomCache = [];

    private array $roomTypeCache = [];

    private array $guestCache = [];

    public function __construct(
        private readonly PmsClientInterface $client,
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
    public function sync(?string $updatedAfter, ?callable $onProgress = null): array
    {
        $ids = $this->client->getUpdatedBookingIds($updatedAfter);

        $synced = 0;
        $failed = 0;
        $consecutiveFailures = 0;
        $maxConsecutiveFailures = 10;

        foreach ($ids as $id) {
            $abort = false;

            try {
                DB::transaction(function () use ($id) {
                    $this->syncBooking((int) $id);
                });
                $synced++;
                $consecutiveFailures = 0;
            } catch (Throwable $e) {
                $failed++;
                $consecutiveFailures++;
                Log::channel('pms_errors')->error('Failed to sync booking', [
                    'booking_id' => $id,
                    'error' => $e->getMessage(),
                ]);

                if ($consecutiveFailures >= $maxConsecutiveFailures) {
                    Log::channel('pms_errors')->error('Aborting sync: too many consecutive failures', [
                        'consecutive_failures' => $consecutiveFailures,
                    ]);
                    $abort = true;
                }
            }

            if ($onProgress) {
                ($onProgress)();
            }

            if ($abort) {
                break;
            }
        }

        return compact('synced', 'failed');
    }

    private function syncBooking(int $id): void
    {
        $bookingData = BookingData::from($this->client->getBooking($id));

        $guests = [];
        foreach ($bookingData->guest_ids as $guestId) {
            if (! isset($this->guestCache[$guestId])) {
                $guestData = GuestData::from($this->client->getGuest($guestId));
                $this->guestCache[$guestId] = $this->guestRepository->upsert($guestData);
            }
            $guests[] = $this->guestCache[$guestId];
        }

        if (! isset($this->roomCache[$bookingData->room_id])) {
            $roomData = RoomData::from($this->client->getRoom($bookingData->room_id));
            $this->roomCache[$bookingData->room_id] = $this->roomRepository->upsert($roomData);
        }
        $room = $this->roomCache[$bookingData->room_id];

        if (! isset($this->roomTypeCache[$bookingData->room_type_id])) {
            $roomTypeData = RoomTypeData::from($this->client->getRoomType($bookingData->room_type_id));
            $this->roomTypeCache[$bookingData->room_type_id] = $this->roomTypeRepository->upsert($roomTypeData);
        }
        $roomType = $this->roomTypeCache[$bookingData->room_type_id];

        $this->bookingRepository->upsert($bookingData, $room, $roomType, $guests);
    }
}
