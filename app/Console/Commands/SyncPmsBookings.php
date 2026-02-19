<?php

namespace App\Console\Commands;

use App\Jobs\SyncBookingChunk;
use App\Services\BookingSyncService;
use App\Services\Pms\PmsClientInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPmsBookings extends Command
{
    protected $signature = 'pms:sync-bookings
        {--fresh : Ignore last sync timestamp and perform a full sync}
        {--sync : Run synchronously instead of dispatching queued jobs}
        {--chunk-size=50 : Number of booking IDs per queued job}';

    protected $description = 'Synchronize bookings from the PMS API';

    public function handle(BookingSyncService $service, PmsClientInterface $client): int
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

        if ($this->option('sync')) {
            return $this->handleSync($service, $lastSyncAt);
        }

        return $this->handleAsync($client, $lastSyncAt);
    }

    private function handleSync(BookingSyncService $service, ?string $lastSyncAt): int
    {
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

    private function handleAsync(PmsClientInterface $client, ?string $lastSyncAt): int
    {
        $ids = $client->getUpdatedBookingIds($lastSyncAt);
        $totalBookings = count($ids);

        if ($totalBookings === 0) {
            $this->info('No bookings to sync.');

            return self::SUCCESS;
        }

        $chunkSize = (int) $this->option('chunk-size');
        $chunks = array_chunk($ids, $chunkSize);
        $totalChunks = count($chunks);

        $bar = $this->output->createProgressBar($totalChunks);
        $bar->start();

        foreach ($chunks as $chunk) {
            SyncBookingChunk::dispatch($chunk);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        DB::table('pms_sync_states')->updateOrInsert(
            ['key' => 'last_sync_at'],
            ['value' => now()->toIso8601String(), 'updated_at' => now()],
        );

        Log::channel('pms_errors')->info('Dispatched sync jobs', [
            'jobs' => $totalChunks,
            'bookings' => $totalBookings,
            'since' => $lastSyncAt,
        ]);

        $this->info("Dispatched {$totalChunks} jobs for {$totalBookings} bookings.");

        return self::SUCCESS;
    }
}
