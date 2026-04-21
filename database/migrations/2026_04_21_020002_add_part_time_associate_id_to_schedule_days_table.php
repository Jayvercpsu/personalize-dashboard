<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedule_days', function (Blueprint $table) {
            $table->foreignId('part_time_associate_id')
                ->nullable()
                ->constrained('associates')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_days', function (Blueprint $table) {
            $table->dropConstrainedForeignId('part_time_associate_id');
        });
    }
};
