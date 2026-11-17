<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The 12 accreditation fields requested by the Quality Manager:
 *  1 blood pressure (systolic + diastolic)   7 chief complaint
 *  2 pulse                                    8 pain score (0-10)
 *  3 temperature                              9 allergies
 *  4 SpO2                                    10 fall risk
 *  5 respiratory rate                        11 recorded by   (automatic, from login)
 *  6 consciousness (AVPU)                    12 recorded at   (automatic, server clock)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->unique()->constrained()->cascadeOnDelete(); // one triage per visit

            $table->unsignedSmallInteger('systolic_bp');
            $table->unsignedSmallInteger('diastolic_bp');
            $table->unsignedSmallInteger('pulse');
            $table->decimal('temperature', 4, 1);          // °C, e.g. 37.2
            $table->unsignedTinyInteger('spo2');           // %
            $table->unsignedTinyInteger('respiratory_rate');
            $table->string('consciousness', 1);            // AVPU: A | V | P | U
            $table->string('chief_complaint', 500);
            $table->unsignedTinyInteger('pain_score');     // 0-10
            $table->string('allergies', 255);              // "NKDA" = no known drug allergies
            $table->string('fall_risk', 10);               // low | medium | high

            // Computed on the server by App\Services\TriageScorer — never trusted from the browser.
            $table->unsignedTinyInteger('news2_score');
            $table->string('risk_level', 10)->index();     // low | medium | high
            $table->json('flags');                         // language-neutral codes, e.g. ["spo2_low","pulse_high"]

            $table->foreignId('recorded_by')->constrained('users');
            $table->dateTime('recorded_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triages');
    }
};
