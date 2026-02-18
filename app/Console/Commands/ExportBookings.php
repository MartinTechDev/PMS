<?php

namespace App\Console\Commands;

use App\Exports\BookingsExport;
use App\Models\Booking;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ExportBookings extends Command
{
    protected $signature = 'pms:export-bookings
        {--status= : Filter by booking status (confirmed, pending, cancelled)}
        {--from= : Filter bookings from this check-in date (Y-m-d)}
        {--to= : Filter bookings until this check-in date (Y-m-d)}
        {--output=bookings-report.xlsx : Output file path}';

    protected $description = 'Export synced bookings to an Excel report';

    public function handle(): int
    {
        $query = Booking::query()
            ->with(['room', 'roomType', 'guests']);

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        if ($from = $this->option('from')) {
            $query->where('check_in', '>=', $from);
        }

        if ($to = $this->option('to')) {
            $query->where('check_in', '<=', $to);
        }

        $bookings = $query->orderBy('check_in')->get();

        if ($bookings->isEmpty()) {
            $this->warn('No bookings match the given filters.');

            return self::SUCCESS;
        }

        $output = $this->option('output');

        $content = Excel::raw(new BookingsExport($bookings), \Maatwebsite\Excel\Excel::XLSX);
        file_put_contents($output, $content);

        $this->info("Exported {$bookings->count()} bookings to {$output}");

        return self::SUCCESS;
    }
}
