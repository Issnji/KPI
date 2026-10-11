<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PositionKpi extends Model
{
    use HasFactory;

    protected $table = 'position_kpis';

    protected $fillable = [
        'position_id',
        'kpi_indicator_id',
        'target',
        'weight',
        'frequency',
        'calculation_type',
        'is_active'
    ];

    protected function casts(): array
{
    return [
        'target'    => 'decimal:2',
        'weight'    => 'decimal:2',
        'is_active' => 'boolean',
    ];
}

    public function kpiSubmissions()
    {
        return $this->hasMany(KpiSubmission::class, 'position_kpi_id', 'id');
    }

    public function kpiIndicator()
    {
        return $this->belongsTo(KpiIndicator::class, 'kpi_indicator_id', 'id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id', 'id');
    }
}
