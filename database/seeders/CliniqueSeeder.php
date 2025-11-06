<?php

namespace Database\Seeders;

use App\Models\Clinique;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class CliniqueSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        $typesEtablissement = [
            'Clinique Privée',
            'Centre Médical',
            'Hôpital Privé',
            'Cabinet Médical',
            'Centre de Santé',
            'Polyclinique'
        ];

        for ($i = 1; $i <= 20; $i++) {
            Clinique::create([
                'nom' => $faker->company . ' Medical Center',
                'email' => 'clinique' . $i . '@example.com',
                'password' => Hash::make('password'),
                'telephone' => $faker->phoneNumber,
                'address' => $faker->address,
                'type_etablissement' => $faker->randomElement($typesEtablissement),
                'description' => $faker->paragraph(3),
                'photo_profil' => null,
                'urgences_24h' => $faker->boolean(60),
                'parking_disponible' => $faker->boolean(70),
                'site_web' => $faker->boolean(50) ? $faker->url : null,
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
            ]);
        }
    }
}