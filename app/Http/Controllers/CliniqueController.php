<?php

namespace App\Http\Controllers;

use App\Models\Clinique;
use Illuminate\Http\Request;

class CliniqueController extends Controller
{
    /**
     * Récupérer toutes les cliniques
     */
    public function index(Request $request)
    {
        try {
            $query = Clinique::query()->with('medecins');

            // Recherche par nom si un terme de recherche est fourni
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where('nom', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('type_etablissement', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('address', 'LIKE', '%' . $searchTerm . '%');
            }

            // Sélectionner uniquement les champs nécessaires
            $cliniques = $query->select([
                'id',
                'nom',
                'email',
                'telephone',
                'address',
                'type_etablissement',
                'description',
                'photo_profil',
                'urgences_24h',
                'parking_disponible',
                'site_web',
                'created_at'
            ])->get();

            return response()->json($cliniques);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des cliniques',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer une clinique spécifique AVEC SES MÉDECINS
     */
    public function show($id)
    {
        try {
            // Charger la clinique avec ses médecins associés (sans la colonne rating)
            $clinique = Clinique::with(['medecins' => function ($query) {
                $query->select([
                    'medecins.id',
                    'medecins.prenom',
                    'medecins.nom',
                    'medecins.specialite',
                    'medecins.photo_profil',
                    'medecins.experience_years',
                    'medecins.telephone',
                    'medecins.email'
                    // Retirer 'medecins.rating' qui n'existe pas
                ])->withPivot('fonction');
            }])->findOrFail($id);

            return response()->json($clinique);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Clinique non trouvée',
                'message' => $e->getMessage()
            ], 404);
        }
    }
}
