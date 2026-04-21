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
        Schema::table('process_path_assignments', function (Blueprint $table) {
            $table->string('path_1', 80)->nullable();
            $table->string('path_2', 80)->nullable();
            $table->string('path_3', 80)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_path_assignments', function (Blueprint $table) {
            $table->dropColumn(['path_1', 'path_2', 'path_3']);
        });
    }
};
