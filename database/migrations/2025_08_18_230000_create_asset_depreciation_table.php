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
        Schema::create('asset_depreciation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_item_id')->constrained('asset_items')->onDelete('cascade');
            $table->decimal('original_value', 12, 2); // Original purchase value
            $table->decimal('salvage_value', 12, 2)->default(0); // Expected salvage value
            $table->unsignedInteger('useful_life_years'); // Expected useful life in years
            $table->enum('depreciation_method', ['straight_line', 'declining_balance', 'sum_of_years'])->default('straight_line');
            $table->decimal('depreciation_rate', 5, 4)->nullable(); // Annual depreciation rate
            $table->decimal('current_value', 12, 2); // Current book value
            $table->decimal('accumulated_depreciation', 12, 2)->default(0); // Total depreciation to date
            $table->date('depreciation_start_date'); // When depreciation calculations begin
            $table->date('last_calculated_date')->nullable(); // Last date depreciation was calculated
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation');
    }
};
