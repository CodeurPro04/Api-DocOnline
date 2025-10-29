<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Medecin;
use App\Models\Clinique;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class MedecinAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|unique:medecins',
            'telephone' => 'nullable|string|max:20',
            'specialite' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'password' => 'required|string|min:6',
            'photo_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'type' => 'required|string|in:independant,clinique',
            'clinique_id' => 'required_if:type,clinique|exists:cliniques,id',
            'fonction' => 'nullable|string|max:255',
        ]);

        try {
            $data = [
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'specialite' => $request->specialite,
                'address' => $request->address,
                'bio' => $request->bio,
                'password' => Hash::make($request->password),
                'type' => $request->type, // Utilise directement 'type'
            ];

            // Si le médecin est rattaché à une clinique, assigner l'ID de la clinique
            if ($request->type === 'clinique' && $request->clinique_id) {
                $data['clinique_id'] = $request->clinique_id;
            }

            if ($request->hasFile('photo_profil')) {
                $data['photo_profil'] = $request->file('photo_profil')->store('photos/medecins', 'public');
            }

            $medecin = Medecin::create($data);

            // Si le médecin est rattaché à une clinique, l'ajouter à la relation many-to-many
            if ($request->type === 'clinique' && $request->clinique_id) {
                $clinique = Clinique::findOrFail($request->clinique_id);

                // Vérifier si la relation many-to-many existe et attacher le médecin
                if (method_exists($clinique, 'medecins')) {
                    $clinique->medecins()->attach($medecin->id, [
                        'fonction' => $request->fonction ?? 'Médecin',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $token = $medecin->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'medecin' => $medecin,
                'photo_url' => $medecin->photo_profil ? asset('storage/' . $medecin->photo_profil) : null,
                'type' => $medecin->type, // Retourne 'type'
                'clinique_id' => $medecin->clinique_id,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'inscription',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $medecin = Medecin::where('email', $request->email)->first();

        if (! $medecin || ! Hash::check($request->password, $medecin->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email ou mot de passe incorrect.'],
            ]);
        }

        $token = $medecin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'medecin' => $medecin,
            'photo_url' => $medecin->photo_profil ? asset('storage/' . $medecin->photo_profil) : null,
        ]);
    }

    public function profile(Request $request)
    {
        $medecin = $request->user();

        // Charger les relations si nécessaire
        $medecin->load(['clinique', 'cliniques']);

        return response()->json($medecin);
    }

    public function updateProfile(Request $request)
    {
        $medecin = $request->user();

        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:medecins,email,' . $medecin->id,
            'telephone' => 'nullable|string|max:20',
            'specialite' => 'sometimes|string|max:255',
            'address' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'photo_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'type' => 'sometimes|string|in:independant,clinique', // Changé de practice_type à type
            'clinique_id' => 'required_if:type,clinique|exists:cliniques,id',
            'fonction' => 'nullable|string|max:255',
        ]);

        $data = $request->only([
            'nom',
            'prenom',
            'email',
            'telephone',
            'specialite',
            'address',
            'bio'
        ]);

        // Gestion du type de pratique
        if ($request->has('type')) {
            $data['type'] = $request->type; // Utilise directement 'type'

            if ($request->type === 'clinique' && $request->clinique_id) {
                $data['clinique_id'] = $request->clinique_id;

                // Ajouter à la relation many-to-many si ce n'est pas déjà fait
                $clinique = Clinique::findOrFail($request->clinique_id);
                if (!$medecin->cliniques->contains($clinique->id)) {
                    $clinique->medecins()->attach($medecin->id, [
                        'fonction' => $request->fonction ?? 'Médecin',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                // Si le médecin devient indépendant, retirer les relations avec les cliniques
                $data['clinique_id'] = null;
                $medecin->cliniques()->detach();
            }
        }

        if ($request->hasFile('photo_profil')) {
            // Supprimer ancienne photo
            if ($medecin->photo_profil && Storage::disk('public')->exists($medecin->photo_profil)) {
                Storage::disk('public')->delete($medecin->photo_profil);
            }
            $data['photo_profil'] = $request->file('photo_profil')->store('photos/medecins', 'public');
        }

        $medecin->update($data);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'medecin' => $medecin,
            'photo_url' => $medecin->photo_profil ? asset('storage/' . $medecin->photo_profil) : null,
        ]);
    }
}
