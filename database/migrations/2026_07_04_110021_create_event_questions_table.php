<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_questions', function (Blueprint $table): void {
            $table->id();
            // organization_id denormalized from the event so tenant scoping is a plain column filter.
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();

            $table->string('label');
            // text|textarea|email|phone|number|date|select|radio|checkbox
            $table->string('type')->default('text');
            $table->json('options')->nullable();   // choices for select/radio/checkbox
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_questions');
    }
};
