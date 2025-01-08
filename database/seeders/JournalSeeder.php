<?php

namespace Database\Seeders;

use App\Models\Journal;
use App\Models\JournalColumn;
use App\Models\JournalType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JournalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        JournalType::create([
            'type' => 'Журнал'
        ]);

        Journal::create([
            'name' => 'Журнал регистрации падений пациентов и посетителей'
        ]);

        JournalColumn::create([
            'header' => 'Колонка 1'
        ]);
        JournalColumn::create([
            'header' => 'Колонка 2'
        ]);
        JournalColumn::create([
            'header' => 'Колонка 3'
        ]);
        JournalColumn::create([
            'header' => 'Колонка 4'
        ]);
        JournalColumn::create([
            'header' => 'Колонка 5'
        ]);
        JournalColumn::create([
            'header' => 'Колонка 6'
        ]);
        JournalColumn::create([
            'header' => 'Колонка 7'
        ]);
        JournalColumn::create([
            'header' => 'Колонка 8'
        ]);
        JournalColumn::create([
            'header' => 'Колонка 9'
        ]);
        JournalColumn::create([
            'header' => 'Колонка 10'
        ]);
        JournalColumn::create([
            'header' => 'Колонка 11'
        ]);
        JournalColumn::create([
            'header' => 'Колонка 12'
        ]);
    }
}
