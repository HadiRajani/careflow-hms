<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->dateTime('arrived_at')->index();       // start of the wait clock (dashboard: wait to triage)
            $table->string('status', 20)->default('waiting')->index(); // waiting | triaged
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['patient_id', 'arrived_at']);   // dashboard: returns within 7 days
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
