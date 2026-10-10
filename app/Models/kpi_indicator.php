<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class kpi_indicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'unit',
        'assessment_type',
        'calculation_type',
        'is_active'
    ];

    public function kpiCategory()
    {
        return $this->belongsTo(kpi_categories::class, 'category_id', 'id');
    }

    public function positionKpis()
    {
        return $this->hasMany(position_kpis::class, 'kpi_indicator_id', 'id');
    }
}
