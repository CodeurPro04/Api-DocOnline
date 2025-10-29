<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Medecin;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * Liste des rendez-vous du patient connecté
     */
    public function index()
    {
        try {
            $patient = Auth::guard('patient')->user();

            if (!$patient) {
                return response()->json(['error' => 'Patient non authentifié'], 401);
            }

            \Log::info('Chargement des rendez-vous pour le patient: ' . $patient->id);

            // Test sans les relations d'abord
            $appointments = Appointment::where('patient_id', $patient->id)
                ->latest('date')
                ->latest('time')
                ->get();

            \Log::info('Nombre de rendez-vous trouvés: ' . $appointments->count());

            $formattedAppointments = $appointments->map(function ($appointment) {
                try {
                    return $this->formatAppointmentForPatient($appointment);
                } catch (\Exception $e) {
                    \Log::error('Erreur formatage rendez-vous ' . $appointment->id . ': ' . $e->getMessage());
                    return [
                        'id' => $appointment->id,
                        'medecin' => 'Médecin non disponible',
                        'specialite' => 'Non spécifié',
                        'date' => $appointment->date,
                        'time' => $appointment->time,
                        'status' => $appointment->status,
                        'consultation_type' => $appointment->consultation_type,
                        'can_cancel' => false,
                    ];
                }
            });

            return response()->json($formattedAppointments);
        } catch (\Exception $e) {
            \Log::error('Erreur dans AppointmentController@index: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Erreur interne du serveur: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Création d’un rendez-vous par un patient
     */
    public function store(Request $request)
    {
        $patient = Auth::guard('patient')->user();
        if (!$patient) return response()->json(['error' => 'Patient non authentifié'], 401);

        $validated = $request->validate([
            'medecin_id' => 'required|exists:medecins,id',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'consultation_type' => 'required|string|max:255',
        ]);

        $date = Carbon::parse($validated['date']);
        $time = Carbon::parse($validated['time']);

        if (!$this->isValidDate($date)) {
            return response()->json(['message' => 'Date invalide ou hors période de réservation.'], 422);
        }

        if (!$this->isValidTime($time)) {
            return response()->json(['message' => 'Les rendez-vous sont possibles entre 08:00 et 19:30.'], 422);
        }

        // Vérifier les conflits
        $conflict = $this->checkConflicts($validated['medecin_id'], $patient->id, $date, $time);
        if ($conflict) return response()->json($conflict, 409);

        DB::beginTransaction();
        try {
            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'medecin_id' => $validated['medecin_id'],
                'date' => $date->format('Y-m-d'),
                'time' => $time->format('H:i'),
                'consultation_type' => $validated['consultation_type'],
                'status' => 'en_attente',
                'created_by' => 'patient',
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Rendez-vous créé avec succès.',
                'appointment' => $this->formatAppointmentForPatient($appointment->load('medecin')),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur création rendez-vous: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur interne lors de la création du rendez-vous.'], 500);
        }
    }

    /**
     * Rendez-vous du médecin connecté
     */
    public function doctorAppointments()
    {
        $medecin = Auth::guard('medecin')->user();

        if (!$medecin) {
            return response()->json(['error' => 'Médecin non authentifié'], 401);
        }

        $appointments = Appointment::with('patient')
            ->where('medecin_id', $medecin->id)
            ->latest('date')
            ->latest('time')
            ->get()
            ->map(fn($a) => $this->formatAppointmentForDoctor($a));

        return response()->json($appointments);
    }

    /**
     * Confirmer un rendez-vous
     */
    public function confirm($id)
    {
        $medecin = Auth::guard('medecin')->user();
        if (!$medecin) return response()->json(['error' => 'Médecin non authentifié'], 401);

        $appointment = Appointment::where('id', $id)
            ->where('medecin_id', $medecin->id)
            ->first();

        if (!$appointment) return response()->json(['error' => 'Rendez-vous non trouvé'], 404);
        if ($appointment->status !== 'en_attente') return response()->json(['error' => 'Rendez-vous déjà traité'], 422);

        $appointment->update(['status' => 'confirmé', 'confirmed_at' => now()]);

        return response()->json(['message' => 'Rendez-vous confirmé', 'appointment' => $appointment]);
    }

    /**
     * Refuser un rendez-vous
     */
    public function reject(Request $request, $id)
    {
        $medecin = Auth::guard('medecin')->user();
        if (!$medecin) return response()->json(['error' => 'Médecin non authentifié'], 401);

        $appointment = Appointment::where('id', $id)
            ->where('medecin_id', $medecin->id)
            ->first();

        if (!$appointment) return response()->json(['error' => 'Rendez-vous non trouvé'], 404);
        if ($appointment->status !== 'en_attente') return response()->json(['error' => 'Rendez-vous déjà traité'], 422);

        $appointment->update([
            'status' => 'refusé',
            'rejected_at' => now(),
            'rejection_reason' => $request->reason ?? 'Raison non précisée',
        ]);

        return response()->json(['message' => 'Rendez-vous refusé']);
    }

    /**
     * Annuler un rendez-vous (patient)
     */
    public function cancel($id)
    {
        $patient = Auth::guard('patient')->user();
        if (!$patient) return response()->json(['error' => 'Patient non authentifié'], 401);

        $appointment = Appointment::where('id', $id)
            ->where('patient_id', $patient->id)
            ->first();

        if (!$appointment) return response()->json(['error' => 'Rendez-vous non trouvé'], 404);

        if (!in_array($appointment->status, ['en_attente', 'confirmé'])) {
            return response()->json(['error' => 'Impossible d’annuler ce rendez-vous.'], 422);
        }

        $dateTime = Carbon::parse($appointment->date . ' ' . $appointment->time);
        if ($dateTime->diffInHours(now()) < 24) {
            return response()->json(['error' => 'Annulation impossible à moins de 24h du rendez-vous.'], 422);
        }

        $appointment->update([
            'status' => 'annulé',
            'cancelled_at' => now(),
            'cancelled_by' => 'patient',
        ]);

        return response()->json(['message' => 'Rendez-vous annulé avec succès.']);
    }

    // === Utilitaires privés === //

    private function checkConflicts($medecinId, $patientId, $date, $time)
    {
        // Patient : pas deux RDV le même jour
        $hasPatientConflict = Appointment::where('patient_id', $patientId)
            ->where('date', $date)
            ->whereIn('status', ['en_attente', 'confirmé'])
            ->exists();

        if ($hasPatientConflict) {
            return ['message' => 'Vous avez déjà un rendez-vous ce jour-là.', 'type' => 'patient'];
        }

        // Médecin : intervalle minimum de 45 min
        $start = $time->copy()->subMinutes(45)->format('H:i');
        $end = $time->copy()->addMinutes(45)->format('H:i');

        $hasDoctorConflict = Appointment::where('medecin_id', $medecinId)
            ->where('date', $date)
            ->whereBetween('time', [$start, $end])
            ->whereIn('status', ['en_attente', 'confirmé'])
            ->exists();

        if ($hasDoctorConflict) {
            return ['message' => 'Le médecin n’est pas disponible à cette heure.', 'type' => 'doctor'];
        }

        return null;
    }

    private function isValidDate(Carbon $date): bool
    {
        return !$date->isSunday() && $date->between(Carbon::today(), Carbon::today()->addMonths(3));
    }

    private function isValidTime(Carbon $time): bool
    {
        return $time->between(Carbon::parse('08:00'), Carbon::parse('19:30'));
    }

    private function formatAppointmentForPatient(Appointment $a)
    {
        return [
            'id' => $a->id,
            'medecin' => $a->medecin ? "Dr. {$a->medecin->prenom} {$a->medecin->nom}" : null,
            'specialite' => $a->medecin->specialite->nom ?? null,
            'date' => $a->date,
            'time' => $a->time,
            'status' => $a->status,
            'consultation_type' => $a->consultation_type,
            'can_cancel' => $this->canBeCancelled($a),
        ];
    }

    private function formatAppointmentForDoctor(Appointment $a)
    {
        return [
            'id' => $a->id,
            'patient' => "{$a->patient->prenom} {$a->patient->nom}",
            'patient_id' => $a->patient->id, // Ajouter l'ID du patient
            'telephone' => $a->patient->telephone, // Ajouter le téléphone
            'address' => $a->patient->address, // Ajouter l'adresse
            'date' => $a->date,
            'time' => $a->time,
            'status' => $a->status,
            'consultation_type' => $a->consultation_type,
        ];
    }

    private function canBeCancelled(Appointment $a)
    {
        if (!in_array($a->status, ['en_attente', 'confirmé'])) return false;
        $dateTime = Carbon::parse($a->date . ' ' . $a->time);
        return $dateTime->diffInHours(now()) >= 24;
    }
}
