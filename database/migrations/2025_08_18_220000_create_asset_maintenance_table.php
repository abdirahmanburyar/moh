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
        Schema::create('asset_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->string('maintenance_type'); // Type of maintenance (preventive, corrective, etc.)
            $table->date('completed_date')->nullable(); // When maintenance was completed
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Who created the maintenance record
            $table->integer('maintenance_range')->default(0); // Every X months (0=one-time, 1, 2, 3, 6, 12, etc.)
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance');
    }
};
