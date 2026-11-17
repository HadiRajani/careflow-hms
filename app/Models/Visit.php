<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Visit extends Model
{
    use HasFactory;

    public const STATUS_WAITING = 'waiting';
    public const STATUS_TRIAGED = 'triaged';

    protected $fillable = ['patient_id', 'arrived_at', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['arrived_at' => 'datetime'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function triage(): HasOne
    {
        return $this->hasOne(Triage::class);
    }
}
