<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'users';

    protected $fillable = ['role_id', 'position_id', 'name', 'email', 'password', 'is_active'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password'  => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function kpiSubmissions()
    {
        return $this->hasMany(KpiSubmission::class, 'user_id', 'id');
    }

    public function roleKey(): ?string
    {
        return $this->role ? Str::lower(trim($this->role->name)) : null;
    }

    public function positionKey(): ?string
    {
        return $this->position ? Str::lower(trim($this->position->name)) : null;
    }

    public function hasRole(string ...$names): bool
    {
        return in_array($this->roleKey(), array_map(fn ($n) => Str::lower(trim($n)), $names), true);
    }

    public function hasPosition(string ...$names): bool
    {
        return in_array($this->positionKey(), array_map(fn ($n) => Str::lower(trim($n)), $names), true);
    }
}