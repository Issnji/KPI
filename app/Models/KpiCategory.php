<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KpiCategory extends Model
{
    use HasFactory;

    protected $table = 'kpi_categories';

    protected $fillable = [
        'name',
        'description',
    ];

    public function kpiIndicators()
    {
        return $this->hasMany(KpiIndicator::class, 'category_id', 'id');
    }
}
