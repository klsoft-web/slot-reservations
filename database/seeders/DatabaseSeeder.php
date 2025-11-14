<?php

namespace Database\Seeders;

use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $slot = new Slot();
        $slot->capacity = 10;
        $slot->remaining = 6;
        $slot->save();

        $slot = new Slot();
        $slot->capacity = 5;
        $slot->remaining = 0;
        $slot->save();
    }
}
