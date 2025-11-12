<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Mail\AppointmentCreated;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    protected $signature = 'test:email';
    protected $description = 'Test email sending';

    public function handle()
    {
        try {
            // Prenez un rendez-vous existant pour tester
            $appointment = Appointment::with(['patient', 'medecin'])->first();
            
            if (!$appointment) {
                $this->error('Aucun rendez-vous trouvé');
                return;
            }

            $this->info('Envoi email au patient: ' . $appointment->patient->email);
            Mail::to($appointment->patient->email)
                ->send(new AppointmentCreated($appointment, 'patient'));

            $this->info('Envoi email au médecin: ' . $appointment->medecin->email);
            Mail::to($appointment->medecin->email)
                ->send(new AppointmentCreated($appointment, 'medecin'));

            $this->info('Emails envoyés avec succès!');
            
        } catch (\Exception $e) {
            $this->error('Erreur: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile());
            $this->error('Line: ' . $e->getLine());
        }
    }
}