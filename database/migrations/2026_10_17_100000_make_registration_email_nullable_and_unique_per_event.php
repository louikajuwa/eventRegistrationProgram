<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
        });

        Schema::table('registrations', function (Blueprint $table): void {
            $table->unique(['event_id', 'email'], 'registrations_event_id_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropUnique('registrations_event_id_email_unique');
        });

        // Restore NOT NULL: blank out any NULLs first so the change cannot fail.
        DB::table('registrations')->whereNull('email')->update(['email' => '']);

        Schema::table('registrations', function (Blueprint $table): void {
            $table->string('email')->nullable(false)->change();
        });
    }
};
