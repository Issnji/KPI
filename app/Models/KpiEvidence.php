<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KpiEvidence extends Model
{
    use HasFactory;

    protected $table = 'kpi_evidences';

    protected $fillable = [
        'submission_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'file_type',
        'created_at'
    ];

    public function kpiSubmission()
    {
        return $this->belongsTo(KpiSubmission::class, 'submission_id', 'id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }
}
