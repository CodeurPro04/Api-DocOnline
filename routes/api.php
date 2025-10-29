<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PatientAuthController;
use App\Http\Controllers\Auth\MedecinAuthController;
use App\Http\Controllers\Auth\CliniqueAuthController;
use App\Http\Controllers\MedecinController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\Api\MedicalChatController;
use App\Http\Controllers\CliniqueController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\FavoriteController; // Ajoutez cette ligne

// ======================
// Routes Public
// ======================
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Récupérer tous les médecins
Route::get('/medecins', [MedecinController::class, 'index']);
Route::get('/medecins/{id}', [MedecinController::class, 'show']);

// Récupérer tous les médecins disponibles à une date donnée
Route::get('/medecins/{id}/availability', [MedecinController::class, 'checkAvailability']);

// Récupérer toutes les cliniques
Route::get('/cliniques', [CliniqueController::class, 'index']);
Route::get('/cliniques/{id}', [CliniqueController::class, 'show']);

// Routes pour l'IA
Route::post('/chat/diagnose', [MedicalChatController::class, 'diagnose']);
Route::post('/chat/voice', [MedicalChatController::class, 'voiceMessage']);

// ======================
// Routes Favoris
// ======================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites/{medecinId}', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{medecinId}', [FavoriteController::class, 'destroy']);
    Route::get('/favorites/check/{medecinId}', [FavoriteController::class, 'check']);
});

// ======================
// Routes Patient
// ======================
Route::prefix('patient')->group(function () {
    Route::post('/register', [PatientAuthController::class, 'register']);
    Route::post('/login', [PatientAuthController::class, 'login']);

    // Routes sécurisées pour le profil patient
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [PatientAuthController::class, 'profile']);
        Route::put('/profile', [PatientAuthController::class, 'updateProfile']);
        Route::get('/appointments', [AppointmentController::class, 'index']);
    });
});

// ======================
// Routes Médecin
// ======================
Route::prefix('medecin')->group(function () {
    Route::post('/register', [MedecinAuthController::class, 'register']);
    Route::post('/login', [MedecinAuthController::class, 'login']);

    // Routes sécurisées pour le profil medecin
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [MedecinAuthController::class, 'profile']);
        Route::put('/profile', [MedecinAuthController::class, 'updateProfile']);
        Route::post('/profile/photo', [MedecinAuthController::class, 'updatePhoto']);
        Route::put('/working-hours', [MedecinAuthController::class, 'updateWorkingHours']);
        Route::get('/appointments', [AppointmentController::class, 'doctorAppointments']);
        Route::patch('/appointments/{id}/confirm', [AppointmentController::class, 'confirm']);
        Route::patch('/appointments/{id}/reject', [AppointmentController::class, 'reject']);
    });
});

// ======================
// Routes Clinique
// ======================
Route::prefix('clinique')->group(function () {
    Route::post('/register', [CliniqueAuthController::class, 'register']);
    Route::post('/login', [CliniqueAuthController::class, 'login']);

    // Routes sécurisées pour la clinique
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [CliniqueAuthController::class, 'profile']);
        Route::put('/profile', [CliniqueAuthController::class, 'updateProfile']);

        // Gestion des médecins
        Route::get('/medecins', [CliniqueAuthController::class, 'getMedecins']);
        Route::post('/medecins/add', [CliniqueAuthController::class, 'addMedecin']);
        Route::delete('/medecins/{medecinId}', [CliniqueAuthController::class, 'removeMedecin']);
    });
});

// ======================
// Routes Rendez-vous
// ======================
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/appointments', [AppointmentController::class, 'store']);
});

// ======================
// Routes Messages
// ======================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/messages/{medecinId}', [MessageController::class, 'getMessages']);
    Route::post('/messages/send', [MessageController::class, 'sendMessage']);
});

/// ======================
// Routes Avis et Notes
// ======================

// Routes publiques - accessibles sans authentification
Route::get('/medecins/{id}/reviews', [ReviewController::class, 'index']);
Route::get('/medecins/{id}/reviews/stats', [ReviewController::class, 'stats']);

// Routes protégées - nécessitent une authentification
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/medecins/{id}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
});
