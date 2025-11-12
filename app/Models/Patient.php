<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'address',
        'password',
        'photo_profil',
        'groupe_sanguin',
        'serologie_vih',
        'antecedents_medicaux',
        'allergies',
        'traitements_chroniques'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_naissance' => 'date',
    ];

    // Relation avec les avis
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function getNameAttribute()
    {
        return $this->nom . ' ' . $this->prenom;
    }

    // Relation avec les favoris
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    // Relation avec les médecins favoris
    public function favoriteMedecins()
    {
        return $this->belongsToMany(Medecin::class, 'favorites')
            ->withTimestamps();
    }

    // Getter pour le nom complet
    public function getFullNameAttribute()
    {
        return "{$this->prenom} {$this->nom}";
    }

    // Getter pour l'URL de la photo de profil
    public function getPhotoUrlAttribute()
    {
        return $this->photo_profil ? asset('storage/' . $this->photo_profil) : null;
    }
}
