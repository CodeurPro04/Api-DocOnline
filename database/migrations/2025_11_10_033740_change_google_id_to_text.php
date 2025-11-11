<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Pour patients
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->text('google_id')->nullable()->change();
        });

        // Pour médecins
        Schema::table('medecins', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->text('google_id')->nullable()->change();
        });

        // Pour cliniques
        Schema::table('cliniques', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->text('google_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('google_id', 191)->nullable()->change();
            $table->unique(['google_id']);
        });

        Schema::table('medecins', function (Blueprint $table) {
            $table->string('google_id', 191)->nullable()->change();
            $table->unique(['google_id']);
        });

        Schema::table('cliniques', function (Blueprint $table) {
            $table->string('google_id', 191)->nullable()->change();
            $table->unique(['google_id']);
        });
    }
};