<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Audit trail: who viewed or changed which patient record, and when.
 *
 * Write an entry from anywhere with one line:
 *     AuditLog::record('patient.viewed', $patient->id);
 *
 * Rows are never edited or deleted (append-only).
 */
class AuditLog extends Model
{
    const UPDATED_AT = null;   // no updated_at column: an audit row never changes

    protected $fillable = ['user_id', 'action', 'patient_id', 'subject_type', 'subject_id', 'meta', 'ip_address'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    /**
     * Save one audit entry for the logged-in user.
     *   $action   what happened, e.g. 'patient.viewed', 'patient.created', 'triage.created'
     *   $meta     small extra details (never full clinical data)
     */
    public static function record(string $action, ?int $patientId, ?Model $subject = null, array $meta = []): void
    {
        self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'patient_id' => $patientId,
            'subject_type' => $subject ? class_basename($subject) : null,   // e.g. 'Visit'
            'subject_id' => $subject?->id,
            'meta' => $meta ?: null,
            'ip_address' => request()->ip(),
        ]);
    }

    // Safety net: block editing or deleting audit rows through the app.
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \LogicException('Audit logs cannot be changed.');
        });
        static::deleting(function () {
            throw new \LogicException('Audit logs cannot be deleted.');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
