<?php

namespace App\Jobs;

use App\Services\BookingSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncBookingChunk implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int|string>  $bookingIds
     */
    public function __construct(
        public readonly array $bookingIds,
    ) {
        $this->onConnection('database');
    }

    public function handle(BookingSyncService $service): void
    {
        $result = $service->syncIds($this->bookingIds);

        Log::channel('pms_errors')->info('Chunk sync completed', [
            'synced' => $result['synced'],
            'failed' => $result['failed'],
            'chunk_size' => count($this->bookingIds),
        ]);
    }
}
