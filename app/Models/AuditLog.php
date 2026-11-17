<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only. Rows are written through App\Services\AuditLogger and are never updated or deleted by the app.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;   // no updated_at column: an audit row never changes

    protected $fillable = ['user_id', 'action', 'patient_id', 'subject_type', 'subject_id', 'meta', 'ip_address'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Defence in depth: block edits/deletes through Eloquent even if a future developer tries.
        static::updating(fn () => throw new LogicException('Audit logs are append-only.'));
        static::deleting(fn () => throw new LogicException('Audit logs are append-only.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
