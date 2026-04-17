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
        Schema::create('hourly_notes', function (Blueprint $table) {
            $table->id();
            $table->date('note_date');
            $table->string('hour_slot', 5);
            $table->text('note')->nullable();
            $table->enum('status', ['resolved', 'pending', 'needs_manager_attention'])->default('pending');
            $table->timestamps();

            $table->unique(['note_date', 'hour_slot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hourly_notes');
    }
};
