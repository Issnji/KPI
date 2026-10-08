<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class kpi_review extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'reviewer_id',
        'review_type',
        'status',
        'score',
        'notes',
        'reviewed_at'
    ];

    public $timestamps = false;

    public function kpiSubmission()
    {
        return $this->belongsTo(KpiSubmission::class, 'submission_id', 'id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id', 'id');
    }
}
