<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public $timestamps = false;

    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }
}
