<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = ['mrn', 'full_name', 'date_of_birth', 'gender', 'phone', 'cnic', 'created_by'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function age(): int
    {
        return $this->date_of_birth->age;
    }
}
