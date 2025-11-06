<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Supprime la contrainte unique sur patient_id + medecin_id
            $table->dropUnique(['patient_id', 'medecin_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Rétablir la contrainte unique si rollback
            $table->unique(['patient_id', 'medecin_id']);
        });
    }
};
