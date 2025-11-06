<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        for ($i = 1; $i <= 20; $i++) {
            Patient::create([
                'nom' => $faker->lastName,
                'prenom' => $faker->firstName,
                'email' => 'patient' . $i . '@example.com',
                'telephone' => $faker->phoneNumber,
                'address' => $faker->address,
                'password' => Hash::make('password'),
                'photo_profil' => null,
                'antecedents_medicaux' => $faker->paragraph(4),
                'groupe_sanguin' => $faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
                'serologie_vih' => $faker->randomElement(['Positif', 'Négatif', 'Inconnu']),
                'allergies' => $faker->sentence(6),
                'traitements_chroniques' => $faker->sentence(8),
                //'date_naissance' => $faker->dateTimeBetween('-80 years', '-18 years'),
                'created_at' => $faker->dateTimeBetween('-2 years', 'now'),
            ]);
        }
    }
}