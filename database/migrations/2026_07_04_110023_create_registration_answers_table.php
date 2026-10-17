<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained('registrations')->cascadeOnDelete();
            // Keep the answer even if the question is later edited/deleted.
            $table->foreignId('event_question_id')->nullable()->constrained('event_questions')->nullOnDelete();

            $table->string('question_label'); // snapshot of the label at submit time
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index('registration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_answers');
    }
};
