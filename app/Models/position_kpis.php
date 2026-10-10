<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class position_kpis extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_id',
        'kpi_indicator_id',
        'target',
        'weight',
        'frequency',
        'calculation_type',
        'is_active'
    ];

    public function kpiSubmissions()
    {
        return $this->hasMany(kpi_submission::class, 'position_kpi_id', 'id');
    }

    public function kpiIndicator()
    {
        return $this->belongsTo(kpi_indicator::class, 'kpi_indicator_id', 'id');
    }

    public function position()
    {
        return $this->belongsTo(position::class, 'position_id', 'id');
    }
}
