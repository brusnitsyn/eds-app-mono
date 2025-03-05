<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'login' => 'dev',
            'name' => 'Брусницын Андрей',
            'email' => 'eds@amurokb.ru',
            'password' => Hash::make("Roe120934!"),
            'role_id' => 1
        ]);

        User::create([
            'login' => 'admin',
            'name' => 'Малолетников Максим',
            'email' => 'eds@amurokb.ru',
            'password' => Hash::make("!234qweR"),
            'role_id' => 1
        ]);

        User::create([
            'login' => 'кузнецоваеп',
            'name' => 'Кузнецова Елизавета',
            'email' => 'eds@amurokb.ru',
            'password' => Hash::make("Asedased123!"),
            'role_id' => 2
        ]);

        User::create([
            'login' => 'nik',
            'name' => 'Николаев Валерий',
            'email' => 'eds@amurokb.ru',
            'password' => Hash::make("Kep4ik200!"),
            'role_id' => 2
        ]);

        User::create([
            'login' => 'slave',
            'name' => 'Сопов Вячеслав',
            'email' => 'eds@amurokb.ru',
            'password' => Hash::make('P@$$W0rd'),
            'role_id' => 2
        ]);
    }
}
