<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KpiReview extends Model
{
    use HasFactory;

    protected $table = 'kpi_reviews';

    protected $fillable = [
        'submission_id',
        'reviewer_id',
        'review_type',
        'status',
        'score',
        'notes',
        'reviewed_at'
    ];

    protected function casts(): array
    {
        return [
            'score'       => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function kpiSubmission()
    {
        return $this->belongsTo(KpiSubmission::class, 'submission_id', 'id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id', 'id');
    }
}
