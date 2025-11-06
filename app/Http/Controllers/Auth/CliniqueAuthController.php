<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Clinique;
use App\Models\Medecin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CliniqueAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|string|email|unique:cliniques',
            'telephone' => 'nullable|string|max:20',
            'address' => 'required|string|max:255',
            'description' => 'nullable|string',
            'password' => 'required|string|min:6',
            'photo_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'type_etablissement' => 'nullable|string|max:255',
            'urgences_24h' => 'nullable|boolean',
            'parking_disponible' => 'nullable|boolean',
            'site_web' => 'nullable|url',
        ]);

        $data = [
            'nom' => $request->nom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'address' => $request->address,
            'description' => $request->description,
            'password' => Hash::make($request->password),
            'type_etablissement' => $request->type_etablissement,
            'urgences_24h' => $request->urgences_24h ?? false,
            'parking_disponible' => $request->parking_disponible ?? false,
            'site_web' => $request->site_web,
        ];

        if ($request->hasFile('photo_profil')) {
            $data['photo_profil'] = $request->file('photo_profil')->store('photos/cliniques', 'public');
        }

        $clinique = Clinique::create($data);

        $token = $clinique->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'clinique' => $clinique,
            'photo_url' => $clinique->photo_profil ? asset('storage/' . $clinique->photo_profil) : null,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $clinique = Clinique::where('email', $request->email)->first();

        if (! $clinique || ! Hash::check($request->password, $clinique->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email ou mot de passe incorrect.'],
            ]);
        }

        $token = $clinique->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'clinique' => $clinique,
            'photo_url' => $clinique->photo_profil ? asset('storage/' . $clinique->photo_profil) : null,
        ]);
    }

    public function profile(Request $request)
    {
        $clinique = $request->user();
        $clinique->load('medecins');
        return response()->json($clinique);
    }

    public function updateProfile(Request $request)
    {
        $clinique = $request->user();

        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:cliniques,email,' . $clinique->id,
            'telephone' => 'nullable|string|max:20',
            'address' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'photo_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'services' => 'nullable|array',
            'equipements' => 'nullable|array',
            'horaires' => 'nullable|array',
            'type_etablissement' => 'nullable|string|max:255',
            'urgences_24h' => 'nullable|boolean',
            'parking_disponible' => 'nullable|boolean',
            'site_web' => 'nullable|url',
        ]);

        $data = $request->only([
            'nom', 'email', 'telephone', 'address', 'description',
            'services', 'equipements', 'horaires', 'type_etablissement',
            'urgences_24h', 'parking_disponible', 'site_web'
        ]);

        if ($request->hasFile('photo_profil')) {
            if ($clinique->photo_profil && Storage::disk('public')->exists($clinique->photo_profil)) {
                Storage::disk('public')->delete($clinique->photo_profil);
            }
            $data['photo_profil'] = $request->file('photo_profil')->store('photos/cliniques', 'public');
        }

        $clinique->update($data);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'clinique' => $clinique,
            'photo_url' => $clinique->photo_profil ? asset('storage/' . $clinique->photo_profil) : null,
        ]);
    }

    /**
     * Mettre à jour le mot de passe de la clinique
     */
    public function updatePassword(Request $request)
    {
        try {
            $clinique = $request->user();

            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            // Vérifier que le mot de passe actuel est correct
            if (!Hash::check($validated['current_password'], $clinique->password)) {
                return response()->json([
                    'error' => 'Mot de passe actuel incorrect',
                    'message' => 'Le mot de passe actuel que vous avez saisi est incorrect.'
                ], 422);
            }

            // Vérifier que le nouveau mot de passe est différent de l'ancien
            if (Hash::check($validated['new_password'], $clinique->password)) {
                return response()->json([
                    'error' => 'Nouveau mot de passe identique',
                    'message' => 'Le nouveau mot de passe doit être différent de l\'ancien.'
                ], 422);
            }

            // Mettre à jour le mot de passe
            $clinique->update([
                'password' => Hash::make($validated['new_password'])
            ]);

            // Supprimer tous les tokens existants (déconnexion de tous les appareils)
            $clinique->tokens()->delete();

            // Créer un nouveau token
            $token = $clinique->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Mot de passe modifié avec succès',
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur modification mot de passe clinique: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la modification du mot de passe',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer le compte de la clinique
     */
    public function deleteAccount(Request $request)
    {
        try {
            $clinique = $request->user();

            $validated = $request->validate([
                'password' => 'required|string',
            ]);

            // Vérifier le mot de passe avant suppression
            if (!Hash::check($validated['password'], $clinique->password)) {
                return response()->json([
                    'error' => 'Mot de passe incorrect',
                    'message' => 'Le mot de passe saisi est incorrect. La suppression du compte a été annulée.'
                ], 422);
            }

            // Supprimer la photo de profil si elle existe
            if ($clinique->photo_profil && Storage::disk('public')->exists($clinique->photo_profil)) {
                Storage::disk('public')->delete($clinique->photo_profil);
            }

            // Détacher tous les médecins de la clinique
            $clinique->medecins()->detach();

            // Supprimer tous les tokens
            $clinique->tokens()->delete();

            // Enregistrer l'email pour les logs
            $email = $clinique->email;

            // Supprimer la clinique
            $clinique->delete();

            Log::info("Compte clinique supprimé: {$email}");

            return response()->json([
                'message' => 'Compte supprimé avec succès'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur suppression compte clinique: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la suppression du compte',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnexion de la clinique
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Déconnexion réussie'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur déconnexion clinique: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la déconnexion',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Ajouter un médecin à la clinique
    public function addMedecin(Request $request)
    {
        $clinique = $request->user();

        $request->validate([
            'medecin_id' => 'required|exists:medecins,id',
            'fonction' => 'nullable|string|max:255',
        ]);

        // Vérifier si le médecin n'est pas déjà dans la clinique
        if ($clinique->medecins()->where('medecin_id', $request->medecin_id)->exists()) {
            return response()->json([
                'message' => 'Ce médecin est déjà attaché à votre clinique'
            ], 422);
        }

        $clinique->medecins()->attach($request->medecin_id, [
            'fonction' => $request->fonction
        ]);

        $clinique->updateMedecinsCount();

        return response()->json([
            'message' => 'Médecin ajouté avec succès',
            'clinique' => $clinique->load('medecins')
        ]);
    }

    // Retirer un médecin de la clinique
    public function removeMedecin(Request $request, $medecinId)
    {
        $clinique = $request->user();

        $clinique->medecins()->detach($medecinId);
        $clinique->updateMedecinsCount();

        return response()->json([
            'message' => 'Médecin retiré avec succès',
            'clinique' => $clinique->load('medecins')
        ]);
    }

    // Lister les médecins de la clinique
    public function getMedecins(Request $request)
    {
        $clinique = $request->user();
        $medecins = $clinique->medecins()->with('user')->get();

        return response()->json($medecins);
    }
}