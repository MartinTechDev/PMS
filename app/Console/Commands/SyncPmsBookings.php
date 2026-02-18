<?php

namespace App\Console\Commands;

use App\Services\BookingSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncPmsBookings extends Command
{
    protected $signature = 'pms:sync-bookings';

    protected $description = 'Synchronize bookings from the PMS API';

    public function handle(BookingSyncService $service): int
    {
        $lastSyncAt = DB::table('pms_sync_states')
            ->where('key', 'last_sync_at')
            ->value('value');

        $this->info('Starting PMS booking sync'.($lastSyncAt ? " (since {$lastSyncAt})" : ' (full sync)'));

        $syncedAt = now()->toIso8601String();

        ['synced' => $synced, 'failed' => $failed] = $service->sync($lastSyncAt);

        if ($failed === 0) {
            DB::table('pms_sync_states')->updateOrInsert(
                ['key' => 'last_sync_at'],
                ['value' => $syncedAt, 'updated_at' => now()],
            );
        }

        $this->info("Sync complete. Synced: {$synced}, Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
