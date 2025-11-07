<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Medecin;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Récupérer les avis d'un médecin
     */
    public function index($medecinId)
    {
        $reviews = Review::with('patient')
            ->where('medecin_id', $medecinId)
            ->verified()
            ->latest()
            ->get();

        return response()->json($reviews);
    }

    /**
     * Ajouter un avis (patient connecté uniquement)
     */
    public function store(Request $request, $medecinId)
    {
        if (!Auth::guard('sanctum')->check()) {
            return response()->json(['error' => 'Vous devez être connecté pour laisser un avis'], 401);
        }

        $user = Auth::guard('sanctum')->user();

        if (!$user instanceof Patient) {
            return response()->json(['error' => 'Vous devez être connecté en tant que patient pour laisser un avis'], 403);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);

        $medecin = Medecin::find($medecinId);
        if (!$medecin) {
            return response()->json(['error' => 'Médecin non trouvé'], 404);
        }

        // Vérifier si le patient a déjà laissé un avis aujourd'hui pour ce médecin
        $hasReviewToday = Review::where('patient_id', $user->id)
            ->where('medecin_id', $medecinId)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($hasReviewToday) {
            return response()->json([
                'error' => 'Vous avez déjà laissé un avis pour ce médecin aujourd\'hui. Vous pourrez en laisser une autre fois.'
            ], 429);
        }

        // Si tout est bon, on crée l'avis
        $review = Review::create([
            'patient_id' => $user->id,
            'medecin_id' => $medecinId,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_verified' => true
        ]);

        $review->load('patient');

        return response()->json([
            'message' => 'Avis ajouté avec succès',
            'review' => $review
        ], 201);
    }

    /**
     * Mettre à jour un avis
     */
    public function update(Request $request, $reviewId)
    {
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'error' => 'Vous devez être connecté pour modifier un avis'
            ], 401);
        }

        $user = Auth::guard('sanctum')->user();

        // Vérifier que l'utilisateur est un patient
        if (!$user instanceof Patient) {
            return response()->json([
                'error' => 'Accès non autorisé'
            ], 403);
        }

        $review = Review::where('id', $reviewId)
            ->where('patient_id', $user->id)
            ->firstOrFail();

        $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|nullable|string|max:1000'
        ]);

        $review->update($request->only(['rating', 'comment']));

        return response()->json([
            'message' => 'Avis mis à jour avec succès',
            'review' => $review->load('patient')
        ]);
    }

    /**
     * Supprimer un avis
     */
    public function destroy($reviewId)
    {
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'error' => 'Vous devez être connecté pour supprimer un avis'
            ], 401);
        }

        $user = Auth::guard('sanctum')->user();

        // Vérifier que l'utilisateur est un patient
        if (!$user instanceof Patient) {
            return response()->json([
                'error' => 'Accès non autorisé'
            ], 403);
        }

        $review = Review::where('id', $reviewId)
            ->where('patient_id', $user->id)
            ->firstOrFail();

        $review->delete();

        return response()->json([
            'message' => 'Avis supprimé avec succès'
        ]);
    }

    /**
     * Statistiques des avis pour un médecin
     */
    public function stats($medecinId)
    {
        $stats = Review::where('medecin_id', $medecinId)
            ->verified()
            ->selectRaw('
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_stars,
                COUNT(CASE WHEN rating = 4 THEN 1 END) as four_stars,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as three_stars,
                COUNT(CASE WHEN rating = 2 THEN 1 END) as two_stars,
                COUNT(CASE WHEN rating = 1 THEN 1 END) as one_stars
            ')
            ->first();

        // Formater les résultats
        $result = [
            'total_reviews' => $stats->total_reviews ?? 0,
            'average_rating' => $stats->average_rating ? round($stats->average_rating, 1) : 0,
            'five_stars' => $stats->five_stars ?? 0,
            'four_stars' => $stats->four_stars ?? 0,
            'three_stars' => $stats->three_stars ?? 0,
            'two_stars' => $stats->two_stars ?? 0,
            'one_stars' => $stats->one_stars ?? 0,
        ];

        return response()->json($result);
    }
}
