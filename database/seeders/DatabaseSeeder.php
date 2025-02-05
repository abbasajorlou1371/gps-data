<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $devices = [
            ['name' => 'Device 1', 'imei' => '867994060623903'],
            ['name' => 'Device 2', 'imei' => '869604069340916'],
        ];

        Device::insert($devices);
    }
}
