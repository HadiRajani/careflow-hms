<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoUserSeeder::class,
            SyntheticOpdSeeder::class,   // ~600 synthetic visits over the last 30 days
        ]);
    }
}
