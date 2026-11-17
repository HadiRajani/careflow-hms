<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Triage extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id', 'systolic_bp', 'diastolic_bp', 'pulse', 'temperature', 'spo2',
        'respiratory_rate', 'consciousness', 'chief_complaint', 'pain_score',
        'allergies', 'fall_risk', 'news2_score', 'risk_level', 'flags',
        'recorded_by', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'flags' => 'array',
            'temperature' => 'decimal:1',
            'recorded_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
