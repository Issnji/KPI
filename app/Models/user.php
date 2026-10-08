<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'position_id',
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * Menyembunyikan kolom password saat diserialisasi ke JSON/Array.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Casting tipe data kolom.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public $timestamps = false;

    // --- RELASI ---

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id', 'id');
    }

    public function kpiSubmissions(): HasMany
    {
        return $this->hasMany(KpiSubmission::class, 'user_id', 'id');
    }

    public function kpiReviews(): HasMany
    {
        return $this->hasMany(KpiReview::class, 'reviewer_id', 'id');
    }

    public function kpiEvidences(): HasMany
    {
        return $this->hasMany(KpiEvidence::class, 'uploaded_by', 'id');
    }
}