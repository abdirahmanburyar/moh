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
        Schema::table('inventories', function (Blueprint $table) {
            $table->decimal('physical_count', 10, 2)->nullable();
            $table->decimal('physical_count_difference', 10, 2)->nullable();
            $table->text('physical_count_remarks')->nullable();
            $table->timestamp('physical_count_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn([
                'physical_count',
                'physical_count_difference',
                'physical_count_remarks',
                'physical_count_date'
            ]);
        });
    }
};
