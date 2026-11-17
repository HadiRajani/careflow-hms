<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('mrn', 20)->unique();          // Medical Record Number shown to staff, e.g. CF-000123
            $table->string('full_name', 120);
            $table->date('date_of_birth')->index();        // indexed: duplicate check looks up by DOB
            $table->string('gender', 10);                  // male | female | other
            $table->string('phone', 20)->index();          // stored normalised (03001234567); duplicate check looks up by phone
            $table->string('cnic', 13)->nullable()->unique(); // digits only, optional; unique = hard duplicate block
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
