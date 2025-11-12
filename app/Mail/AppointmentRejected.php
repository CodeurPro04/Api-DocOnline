<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    public $patient;
    public $medecin;

    public function __construct(Appointment $appointment)
    {
        // Charger les relations si elles ne sont pas déjà chargées
        $this->appointment = $appointment->load(['patient', 'medecin']);
        $this->patient = $appointment->patient;
        $this->medecin = $appointment->medecin;
    }

    public function build()
    {
        return $this->subject('Rendez-vous refusé - Meetmedpro')
                    ->view('emails.appointment_rejected')
                    ->with([
                        'appointment' => $this->appointment,
                        'patient' => $this->patient,
                        'medecin' => $this->medecin,
                    ]);
    }
}