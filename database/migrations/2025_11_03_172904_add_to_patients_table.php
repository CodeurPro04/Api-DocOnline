<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->enum('groupe_sanguin', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->enum('serologie_vih', ['positif', 'negatif', 'inconnu'])->nullable();
            $table->text('antecedents_medicaux')->nullable();
            $table->text('allergies')->nullable();
            $table->text('traitements_chroniques')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'groupe_sanguin',
                'serologie_vih', 
                'antecedents_medicaux',
                'allergies',
                'traitements_chroniques'
            ]);
        });
    }
};