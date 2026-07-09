<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// Nota: los seeders individuales se referencian por nombre de clase (autoload PSR-4)

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            AdminSeeder::class,
            TouristPlaceSeeder::class,
            NewsSeeder::class,
            EventSeeder::class,
            GallerySeeder::class,
        ]);
    }
}