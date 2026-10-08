<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class kpi_categories extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public $timestamps = false;

    public function kpiIndicators()
    {
        return $this->hasMany(kpi_indicator::class, 'category_id', 'id');
    }
}
