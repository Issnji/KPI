<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KpiIndicator extends Model
{
    use HasFactory;

    protected $table = 'kpi_indicators';

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
        return $this->belongsTo(KpiCategory::class, 'category_id', 'id');
    }

    public function positionKpis()
    {
        return $this->hasMany(PositionKpis::class, 'kpi_indicator_id', 'id');
    }
}
