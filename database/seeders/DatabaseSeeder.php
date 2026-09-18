<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // same seed -> same names and numbers on every run
        // (dates are relative to today, so they move with the day you run it)
        fake()->seed(config('lpmc.faker_seed'));

        $this->call(HospitalSeeder::class);
    }
}
