<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    public $recipientType; // 'patient' ou 'medecin'
    public $patient;
    public $medecin;

    public function __construct(Appointment $appointment, $recipientType)
    {
        $this->appointment = $appointment;
        $this->recipientType = $recipientType;
        $this->patient = $appointment->patient;
        $this->medecin = $appointment->medecin;
    }

    public function build()
    {
        $subject = $this->recipientType === 'patient'
            ? 'Votre demande de rendez-vous a été enregistrée'
            : 'Nouveau rendez-vous reçu';

        return $this->subject($subject)
            ->view('emails.appointment_created')
            ->with([
                'appointment' => $this->appointment,
                'recipientType' => $this->recipientType,
                'patient' => $this->appointment->patient,  // 👈 ajout
                'medecin' => $this->appointment->medecin,  // 👈 ajout
            ]);
    }
}
