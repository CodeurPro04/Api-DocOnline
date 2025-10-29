<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'medecin_id',
        'rating',
        'comment',
        'is_verified'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'rating' => 'integer'
    ];

    // Relation avec le patient
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    // Relation avec le médecin
    public function medecin()
    {
        return $this->belongsTo(Medecin::class);
    }

    // Scope pour les avis vérifiés
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    // Scope pour les avis d'un médecin spécifique
    public function scopeForMedecin($query, $medecinId)
    {
        return $query->where('medecin_id', $medecinId);
    }
}