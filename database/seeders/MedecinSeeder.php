<?php

namespace Database\Seeders;

use App\Models\Medecin;
use App\Models\Clinique;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class MedecinSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        $specialites = [
            'Cardiologie',
            'Dermatologie',
            'Pédiatrie',
            'Gynécologie',
            'Neurologie',
            'Orthopédie',
            'ORL',
            'Ophtalmologie',
            'Psychiatrie',
            'Médecine Générale',
            'Radiologie',
            'Chirurgie Générale'
        ];

        $langues = [
            ['Français'],
            ['Français', 'Anglais'],
            ['Français', 'Anglais', 'Espagnol'],
            ['Français', 'Arabe'],
            ['Français', 'Allemand']
        ];

        $horaires = [
            'Lundi-Vendredi: 8h-18h',
            'Lundi-Vendredi: 9h-17h, Samedi: 9h-13h',
            'Lundi-Samedi: 8h-20h',
            'Mardi-Samedi: 9h-19h'
        ];

        $cliniques = Clinique::all();

        for ($i = 1; $i <= 20; $i++) {
            $type = $faker->randomElement(['independant', 'clinique']);
            $clinique = $type === 'clinique' ? $cliniques->random() : null;

            $medecin = Medecin::create([
                'nom' => $faker->lastName,
                'prenom' => $faker->firstName,
                'email' => 'medecin' . $i . '@example.com',
                'telephone' => $faker->phoneNumber,
                'specialite' => $faker->randomElement($specialites),
                'address' => $faker->address,
                'bio' => $faker->paragraph(4),
                'password' => Hash::make('password'),
                'photo_profil' => null,
                'experience_years' => $faker->numberBetween(1, 35),
                'languages' => $faker->randomElement($langues),
                'professional_background' => $faker->paragraph(2),
                'consultation_price' => $faker->numberBetween(3000, 25000),
                'insurance_accepted' => $faker->boolean(80),
                'working_hours' => $faker->randomElement($horaires),
                'clinique_id' => $clinique?->id,
                'type' => $type,
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
            ]);

            // Si le médecin travaille en clinique, on peut l'attacher à d'autres cliniques aussi
            if ($type === 'clinique' && $faker->boolean(30)) {
                $autresCliniques = $cliniques->where('id', '!=', $clinique->id)
                    ->random($faker->numberBetween(1, 2));
                
                foreach ($autresCliniques as $autreClinique) {
                    $medecin->cliniques()->attach($autreClinique->id, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Attacher le médecin à sa clinique principale via la table pivot
            if ($clinique) {
                $medecin->cliniques()->attach($clinique->id, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}