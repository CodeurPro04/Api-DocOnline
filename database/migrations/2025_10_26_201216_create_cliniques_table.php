<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliniques', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('email')->unique();
            $table->string('telephone')->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->string('photo_profil')->nullable();
            $table->string('password');
            $table->integer('nombre_medecins')->default(0);
            $table->json('services')->nullable(); // ["Cardiologie", "Pédiatrie"]
            $table->json('equipements')->nullable(); // ["Scanner", "IRM"]
            $table->json('horaires')->nullable();
            $table->string('type_etablissement')->nullable(); // Clinique privée, Centre médical, Hôpital
            $table->boolean('urgences_24h')->default(false);
            $table->boolean('parking_disponible')->default(false);
            $table->string('site_web')->nullable();
            $table->timestamps();
        });

        // Table pivot pour lier cliniques et médecins
        Schema::create('clinique_medecin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinique_id')->constrained()->onDelete('cascade');
            $table->foreignId('medecin_id')->constrained()->onDelete('cascade');
            $table->string('fonction')->nullable(); // Chef de service, Médecin attaché, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinique_medecin');
        Schema::dropIfExists('cliniques');
    }
};