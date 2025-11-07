<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Medecin;
use App\Models\Clinique;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MedecinAuthController extends Controller
{
    /**
     * Inscription d'un nouveau médecin
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'nom' => 'required|string|max:255',
                'prenom' => 'required|string|max:255',
                'email' => 'required|string|email|unique:medecins,email',
                'password' => 'required|string|min:6',
                'telephone' => 'nullable|string|max:20',
                'specialite' => 'required|string|max:255',
                'address' => 'nullable|string|max:500',
                'bio' => 'nullable|string',
                'photo_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'type' => 'required|string|in:independant,clinique',
                'clinique_id' => 'required_if:type,clinique|nullable|exists:cliniques,id',
                'fonction' => 'nullable|string|max:255',
            ]);

            // Préparer les données du médecin
            $medecinData = [
                'nom' => $validated['nom'],
                'prenom' => $validated['prenom'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'telephone' => $validated['telephone'] ?? null,
                'specialite' => $validated['specialite'],
                'address' => $validated['address'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'type' => $validated['type'],
                'clinique_id' => $validated['type'] === 'clinique' ? $validated['clinique_id'] : null,
            ];

            // Gérer l'upload de la photo
            if ($request->hasFile('photo_profil')) {
                $medecinData['photo_profil'] = $request->file('photo_profil')->store('photos/medecins', 'public');
            }

            // Créer le médecin
            $medecin = Medecin::create($medecinData);

            // Si rattaché à une clinique, créer la relation many-to-many
            if ($validated['type'] === 'clinique' && isset($validated['clinique_id'])) {
                $clinique = Clinique::findOrFail($validated['clinique_id']);

                if (method_exists($clinique, 'medecins')) {
                    $clinique->medecins()->attach($medecin->id, [
                        'fonction' => $validated['fonction'] ?? 'Médecin',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Générer le token
            $token = $medecin->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Inscription réussie',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'medecin' => $medecin,
                'photo_url' => $medecin->photo_profil ? asset('storage/' . $medecin->photo_profil) : null,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur inscription médecin: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de l\'inscription',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connexion d'un médecin
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $medecin = Medecin::where('email', $validated['email'])->first();

            if (!$medecin || !Hash::check($validated['password'], $medecin->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Email ou mot de passe incorrect.'],
                ]);
            }

            // Supprimer les anciens tokens (optionnel)
            $medecin->tokens()->delete();

            // Créer un nouveau token
            $token = $medecin->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Connexion réussie',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'medecin' => $medecin,
                'photo_url' => $medecin->photo_profil ? asset('storage/' . $medecin->photo_profil) : null,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur connexion médecin: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la connexion',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer le profil du médecin connecté
     */
    public function profile(Request $request)
    {
        try {
            $medecin = $request->user();

            // Charger les relations
            $medecin->load(['clinique', 'cliniques']);

            // Décoder working_hours si c'est une chaîne JSON
            if ($medecin->working_hours && is_string($medecin->working_hours)) {
                $medecin->working_hours = json_decode($medecin->working_hours);
            }

            return response()->json($medecin, 200);
        } catch (\Exception $e) {
            Log::error('Erreur récupération profil médecin: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la récupération du profil',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le profil du médecin
     */
    public function updateProfile(Request $request)
    {
        try {
            $medecin = $request->user();

            $validated = $request->validate([
                'nom' => 'sometimes|string|max:255',
                'prenom' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|unique:medecins,email,' . $medecin->id,
                'telephone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'specialite' => 'nullable|string|max:255',
                'experience_years' => 'nullable|integer|min:0|max:100',
                'languages' => 'nullable|string',
                'professional_background' => 'nullable|string',
                'consultation_price' => 'nullable|integer|min:0',
                'insurance_accepted' => 'nullable|boolean',
                'bio' => 'nullable|string',
                'working_hours' => 'nullable|array',
                'type' => 'sometimes|string|in:independant,clinique',
                'clinique_id' => 'required_if:type,clinique|nullable|exists:cliniques,id',
                'fonction' => 'nullable|string|max:255',
            ]);

            // Préparer les données à mettre à jour
            $dataToUpdate = collect($validated)->except(['type', 'clinique_id', 'fonction', 'working_hours'])->toArray();

            // Gérer les horaires de travail
            if (isset($validated['working_hours'])) {
                $dataToUpdate['working_hours'] = json_encode($validated['working_hours']);
            }

            // Gérer le type de pratique
            if (isset($validated['type'])) {
                $dataToUpdate['type'] = $validated['type'];

                if ($validated['type'] === 'clinique' && isset($validated['clinique_id'])) {
                    $dataToUpdate['clinique_id'] = $validated['clinique_id'];

                    // Ajouter à la relation many-to-many si nécessaire
                    $clinique = Clinique::findOrFail($validated['clinique_id']);
                    if (!$medecin->cliniques->contains($clinique->id)) {
                        $clinique->medecins()->attach($medecin->id, [
                            'fonction' => $validated['fonction'] ?? 'Médecin',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    // Si indépendant, retirer les relations cliniques
                    $dataToUpdate['clinique_id'] = null;
                    $medecin->cliniques()->detach();
                }
            }

            // Mettre à jour le médecin
            $medecin->update($dataToUpdate);

            // Recharger le médecin avec les relations
            $medecin->refresh();
            $medecin->load(['clinique', 'cliniques']);

            // Décoder working_hours pour le retour
            if ($medecin->working_hours && is_string($medecin->working_hours)) {
                $medecin->working_hours = json_decode($medecin->working_hours);
            }

            return response()->json($medecin, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour profil médecin: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la mise à jour du profil',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour la photo de profil
     */
    public function updatePhoto(Request $request)
    {
        try {
            $medecin = $request->user();

            // Validation stricte + taille max 5 Mo
            $validated = $request->validate([
                'photo_profil' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120', // 5120 = 5 Mo
            ]);

            // Suppression de l'ancienne photo si elle existe
            if ($medecin->photo_profil && Storage::disk('public')->exists($medecin->photo_profil)) {
                Storage::disk('public')->delete($medecin->photo_profil);
            }

            // Sauvegarde de la nouvelle photo
            $photoPath = $request->file('photo_profil')->store('photos/medecins', 'public');
            $medecin->update(['photo_profil' => $photoPath]);

            return response()->json([
                'message' => 'Photo de profil mise à jour avec succès',
                'photo_profil' => $photoPath,
                'photo_url' => asset('storage/' . $photoPath),
            ], 200);
        } catch (ValidationException $e) {
            // Message utilisateur clair
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first() ?? 'Erreur de validation du fichier.';

            return response()->json([
                'error' => 'Erreur de validation',
                'message' => $firstError,
            ], 422);
        } catch (\Exception $e) {
            // Log interne + message générique côté front
            Log::error('Erreur mise à jour photo médecin: ' . $e->getMessage());

            return response()->json([
                'error' => 'Erreur interne',
                'message' => 'Une erreur est survenue lors de la mise à jour de la photo.',
            ], 500);
        }
    }


    /**
     * Mettre à jour les horaires de travail (route séparée si nécessaire)
     */
    public function updateWorkingHours(Request $request)
    {
        try {
            $medecin = $request->user();

            $validated = $request->validate([
                'working_hours' => 'required|array',
                'working_hours.*.day' => 'required|string',
                'working_hours.*.hours' => 'required|string',
            ]);

            // Convertir en JSON
            $workingHours = json_encode($validated['working_hours']);

            // Mettre à jour
            $medecin->update(['working_hours' => $workingHours]);

            return response()->json([
                'message' => 'Horaires mis à jour avec succès',
                'working_hours' => json_decode($workingHours),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour horaires médecin: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la mise à jour des horaires',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnexion (optionnel)
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Déconnexion réussie'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur déconnexion médecin: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la déconnexion',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function updatePassword(Request $request)
    {
        try {
            $medecin = $request->user();

            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            // Vérifier que le mot de passe actuel est correct
            if (!Hash::check($validated['current_password'], $medecin->password)) {
                return response()->json([
                    'error' => 'Mot de passe actuel incorrect',
                    'message' => 'Le mot de passe actuel que vous avez saisi est incorrect.'
                ], 422);
            }

            // Vérifier que le nouveau mot de passe est différent de l'ancien
            if (Hash::check($validated['new_password'], $medecin->password)) {
                return response()->json([
                    'error' => 'Nouveau mot de passe identique',
                    'message' => 'Le nouveau mot de passe doit être différent de l\'ancien.'
                ], 422);
            }

            // Mettre à jour le mot de passe
            $medecin->update([
                'password' => Hash::make($validated['new_password'])
            ]);

            // Supprimer tous les tokens existants (déconnexion de tous les appareils)
            $medecin->tokens()->delete();

            // Créer un nouveau token
            $token = $medecin->createToken('auth_token')->plainTextToken;

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
            Log::error('Erreur modification mot de passe médecin: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la modification du mot de passe',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer le compte du médecin
     */
    public function deleteAccount(Request $request)
    {
        try {
            $medecin = $request->user();

            $validated = $request->validate([
                'password' => 'required|string',
            ]);

            // Vérifier le mot de passe avant suppression
            if (!Hash::check($validated['password'], $medecin->password)) {
                return response()->json([
                    'error' => 'Mot de passe incorrect',
                    'message' => 'Le mot de passe saisi est incorrect. La suppression du compte a été annulée.'
                ], 422);
            }

            // Supprimer la photo de profil si elle existe
            if ($medecin->photo_profil && Storage::disk('public')->exists($medecin->photo_profil)) {
                Storage::disk('public')->delete($medecin->photo_profil);
            }

            // Supprimer tous les tokens
            $medecin->tokens()->delete();

            // Enregistrer l'email pour les logs (optionnel)
            $email = $medecin->email;

            // Supprimer le médecin
            $medecin->delete();

            Log::info("Compte médecin supprimé: {$email}");

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
            Log::error('Erreur suppression compte médecin: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la suppression du compte',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
