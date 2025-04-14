<?php

namespace App\Exports;

use App\Models\Staff;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected Collection $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection(): \Illuminate\Support\Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'ID',
            'ФИО',
            'Должность',
            'ИНН',
            'СНИЛС',
//            'Подразделение',
            'Сертификат',
            'Действительный',
            'Действует с',
            'Действует по',
            'Закрытый ключ по',
        ];
    }

    public function map($staff): array
    {
        return [
            $staff->id,
            $staff->full_name,
            $staff->job_title,
            $staff->inn,
            $staff->snils,
//            $staff->division ? $staff->division->name : '',
            $staff->certification ? $staff->certification->serial_number : '',
            $staff->certification ? ($staff->certification->is_valid ? 'Да' : 'Нет') : '',
            $staff->certification ? ($staff->certification->valid_from ? Carbon::createFromTimestampMs($staff->certification->valid_from)->format('d.m.Y H:i') : '') : '',
            $staff->certification ? ($staff->certification->valid_to ? Carbon::createFromTimestampMs($staff->certification->valid_to)->format('d.m.Y H:i') : '') : '',
            $staff->certification ? ($staff->certification->close_key_valid_to ? Carbon::createFromTimestampMs($staff->certification->close_key_valid_to)->format('d.m.Y H:i') : '') : '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Заголовки жирным
            1 => ['font' => ['bold' => true]],

            // Автоперенос для длинных текстов
            'A:Z' => [
                'alignment' => [
                    'wrapText' => true
                ]
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [];
    }
}
