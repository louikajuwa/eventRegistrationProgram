<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();

            // Promoted from mapped answers — drive the confirmation email, list + search.
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();

            // Attendance check-in (manual toggle).
            $table->boolean('attended')->default(false);
            $table->dateTime('checked_in_at')->nullable();

            $table->string('reference')->unique(); // REG-YYYYMMDD-XXXXXX
            $table->timestamps();

            $table->index(['event_id', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
