<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KpiSubmission extends Model
{
    use HasFactory;
    
    protected $table = 'kpi_submissions';

    protected $fillable = [
        'position_kpi_id',
        'user_id',
        'period_start',
        'period_end',
        'answer_yes_no',
        'numeric_value',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'answer_yes_no' => 'boolean',
        'numeric_value' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    public function positionKpis(): BelongsTo
    {
        return $this->belongsTo(position_kpis::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(KpiEvidence::class, 'submission_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(KpiReview::class, 'submission_id');
    }
}