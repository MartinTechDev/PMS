<?php

namespace App\Exports;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BookingsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly Collection $bookings,
    ) {}

    public function collection(): Collection
    {
        return $this->bookings;
    }

    public function headings(): array
    {
        return [
            '#',
            'Booking ID',
            'Room',
            'Room Type',
            'Guests',
            'Check-in',
            'Check-out',
            'Nights',
            'Status',
            'Notes',
        ];
    }

    /** @param Booking $row */
    public function map($row): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $row->external_id,
            $row->room?->name ?? '—',
            $row->roomType?->name ?? '—',
            $row->guests->map(fn ($g) => "{$g->first_name} {$g->last_name}")->implode(', '),
            $row->check_in->format('Y-m-d'),
            $row->check_out->format('Y-m-d'),
            $row->check_in->diffInDays($row->check_out),
            $row->status instanceof BookingStatus ? $row->status->value : $row->status,
            $row->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
