<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoUserSeeder::class,
            // SyntheticOpdSeeder::class,  // added in the seeding block (500+ visits over 30 days)
        ]);
    }
}
