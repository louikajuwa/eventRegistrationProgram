<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            // active | inactive — an admin-managed lifecycle flag, independent of attendance.
            $table->string('status')->default('active')->after('phone');
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropIndex(['event_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
