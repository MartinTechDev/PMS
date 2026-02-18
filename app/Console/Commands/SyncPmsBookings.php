<?php

namespace App\Console\Commands;

use App\Services\BookingSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPmsBookings extends Command
{
    protected $signature = 'pms:sync-bookings {--fresh : Ignore last sync timestamp and perform a full sync}';

    protected $description = 'Synchronize bookings from the PMS API';

    public function handle(BookingSyncService $service): int
    {
        $lastSyncAt = $this->option('fresh')
            ? null
            : DB::table('pms_sync_states')->where('key', 'last_sync_at')->value('value');

        $label = match (true) {
            $this->option('fresh') => 'full sync (forced)',
            $lastSyncAt !== null => "since {$lastSyncAt}",
            default => 'full sync',
        };

        $this->info("Starting PMS booking sync ({$label})");

        $syncedAt = now()->toIso8601String();

        $bar = $this->output->createProgressBar();
        $bar->start();

        ['synced' => $synced, 'failed' => $failed] = $service->sync(
            $lastSyncAt,
            fn () => $bar->advance(),
        );

        $bar->finish();
        $this->newLine();

        if ($failed === 0) {
            DB::table('pms_sync_states')->updateOrInsert(
                ['key' => 'last_sync_at'],
                ['value' => $syncedAt, 'updated_at' => now()],
            );
        }

        Log::channel('pms_errors')->info('Sync completed', [
            'synced' => $synced,
            'failed' => $failed,
            'since' => $lastSyncAt,
        ]);

        $this->info("Sync complete. Synced: {$synced}, Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
