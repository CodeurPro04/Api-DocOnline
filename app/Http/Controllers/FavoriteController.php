<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Medecin;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Récupérer tous les favoris du patient avec les détails des médecins
     */
    public function index()
    {
        try {
            $patient = Auth::guard('patient')->user();

            if (!$patient) {
                return response()->json(['error' => 'Patient non authentifié'], 401);
            }

            $favorites = Favorite::where('patient_id', $patient->id)
                ->with('medecin')
                ->get()
                ->map(function ($favorite) {
                    return $favorite->medecin;
                });

            return response()->json($favorites);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la récupération des favoris'], 500);
        }
    }

    /**
     * Ajouter un médecin aux favoris
     */
    public function store($medecinId)
    {
        try {
            $patient = Auth::guard('patient')->user();

            if (!$patient) {
                return response()->json(['error' => 'Patient non authentifié'], 401);
            }

            // Vérifier si le médecin existe
            $medecin = Medecin::find($medecinId);
            if (!$medecin) {
                return response()->json(['error' => 'Médecin non trouvé'], 404);
            }

            // Vérifier si déjà en favori
            $existingFavorite = Favorite::where('patient_id', $patient->id)
                ->where('medecin_id', $medecinId)
                ->first();

            if ($existingFavorite) {
                return response()->json(['message' => 'Déjà dans les favoris'], 200);
            }

            // Ajouter aux favoris
            $favorite = Favorite::create([
                'patient_id' => $patient->id,
                'medecin_id' => $medecinId,
            ]);

            return response()->json([
                'message' => 'Médecin ajouté aux favoris',
                'favorite' => $favorite
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de l\'ajout aux favoris'], 500);
        }
    }

    /**
     * Retirer un médecin des favoris
     */
    public function destroy($medecinId)
    {
        try {
            $patient = Auth::guard('patient')->user();

            if (!$patient) {
                return response()->json(['error' => 'Patient non authentifié'], 401);
            }

            $favorite = Favorite::where('patient_id', $patient->id)
                ->where('medecin_id', $medecinId)
                ->first();

            if (!$favorite) {
                return response()->json(['error' => 'Favori non trouvé'], 404);
            }

            $favorite->delete();

            return response()->json(['message' => 'Médecin retiré des favoris']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la suppression du favori'], 500);
        }
    }

    /**
     * Vérifier si un médecin est dans les favoris
     */
    public function check($medecinId)
    {
        try {
            $patient = Auth::guard('patient')->user();

            if (!$patient) {
                return response()->json(['is_favorite' => false]);
            }

            $isFavorite = Favorite::where('patient_id', $patient->id)
                ->where('medecin_id', $medecinId)
                ->exists();

            return response()->json(['is_favorite' => $isFavorite]);
        } catch (\Exception $e) {
            return response()->json(['is_favorite' => false]);
        }
    }
}
