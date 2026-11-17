<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit trail: who viewed or changed which patient record, and when.
 * No updated_at, and the app exposes no route to edit or delete rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);                  // e.g. patient.viewed, patient.created, triage.created
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type', 40)->nullable(); // patient | visit | triage
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();              // small context only; never full clinical data
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['patient_id', 'created_at']);   // "who touched this patient?"
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
