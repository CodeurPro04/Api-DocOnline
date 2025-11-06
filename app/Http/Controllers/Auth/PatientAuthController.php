<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PatientAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|unique:patients',
            'telephone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'password' => 'required|string|min:6',
            'photo_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = [
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'address' => $request->address,
            'password' => Hash::make($request->password),
        ];

        if ($request->hasFile('photo_profil')) {
            $data['photo_profil'] = $request->file('photo_profil')->store('photos/patients', 'public');
        }

        $patient = Patient::create($data);

        $token = $patient->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'patient' => $patient,
            'photo_url' => $patient->photo_profil ? asset('storage/' . $patient->photo_profil) : null,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $patient = Patient::where('email', $request->email)->first();

        if (! $patient || ! Hash::check($request->password, $patient->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email ou mot de passe incorrect.'],
            ]);
        }

        $token = $patient->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'patient' => $patient,
            'photo_url' => $patient->photo_profil ? asset('storage/' . $patient->photo_profil) : null,
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $patient = $request->user();

        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:patients,email,' . $patient->id,
            'telephone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'photo_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = $request->only(['nom','prenom','email','telephone','address']);

        if ($request->hasFile('photo_profil')) {
            // Supprimer l'ancienne photo si elle existe
            if ($patient->photo_profil && Storage::disk('public')->exists($patient->photo_profil)) {
                Storage::disk('public')->delete($patient->photo_profil);
            }

            $data['photo_profil'] = $request->file('photo_profil')->store('photos/patients', 'public');
        }

        $patient->update($data);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'patient' => $patient,
            'photo_url' => $patient->photo_profil ? asset('storage/' . $patient->photo_profil) : null,
        ]);
    }

    /**
     * Mettre à jour le mot de passe du patient
     */
    public function updatePassword(Request $request)
    {
        try {
            $patient = $request->user();

            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            // Vérifier que le mot de passe actuel est correct
            if (!Hash::check($validated['current_password'], $patient->password)) {
                return response()->json([
                    'error' => 'Mot de passe actuel incorrect',
                    'message' => 'Le mot de passe actuel que vous avez saisi est incorrect.'
                ], 422);
            }

            // Vérifier que le nouveau mot de passe est différent de l'ancien
            if (Hash::check($validated['new_password'], $patient->password)) {
                return response()->json([
                    'error' => 'Nouveau mot de passe identique',
                    'message' => 'Le nouveau mot de passe doit être différent de l\'ancien.'
                ], 422);
            }

            // Mettre à jour le mot de passe
            $patient->update([
                'password' => Hash::make($validated['new_password'])
            ]);

            // Supprimer tous les tokens existants (déconnexion de tous les appareils)
            $patient->tokens()->delete();

            // Créer un nouveau token
            $token = $patient->createToken('auth_token')->plainTextToken;

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
            Log::error('Erreur modification mot de passe patient: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la modification du mot de passe',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer le compte du patient
     */
    public function deleteAccount(Request $request)
    {
        try {
            $patient = $request->user();

            $validated = $request->validate([
                'password' => 'required|string',
            ]);

            // Vérifier le mot de passe avant suppression
            if (!Hash::check($validated['password'], $patient->password)) {
                return response()->json([
                    'error' => 'Mot de passe incorrect',
                    'message' => 'Le mot de passe saisi est incorrect. La suppression du compte a été annulée.'
                ], 422);
            }

            // Supprimer la photo de profil si elle existe
            if ($patient->photo_profil && Storage::disk('public')->exists($patient->photo_profil)) {
                Storage::disk('public')->delete($patient->photo_profil);
            }

            // Supprimer tous les tokens
            $patient->tokens()->delete();

            // Enregistrer l'email pour les logs (optionnel)
            $email = $patient->email;

            // Supprimer le patient
            $patient->delete();

            Log::info("Compte patient supprimé: {$email}");

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
            Log::error('Erreur suppression compte patient: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la suppression du compte',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnexion du patient
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Déconnexion réussie'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur déconnexion patient: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la déconnexion',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}