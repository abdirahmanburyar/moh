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
            Schema::create('assets', function (Blueprint $table) {
                $table->id();
                $table->string('asset_number')->unique(); // Asset number (e.g., ASSET-001)
                $table->date('acquisition_date'); // When the asset was acquired
                $table->foreignId('fund_source_id')->constrained('fund_sources');
                $table->foreignId('region_id')->constrained('regions');
                $table->foreignId('asset_location_id')->constrained('asset_locations');

                $table->string('status')->default('pending_approval');

                // approvals
                $table->foreignId('submitted_by')->constrained('users');
                $table->timestamp('submitted_at');

                $table->foreignId('reviewed_by')->nullable()->constrained('users');
                $table->timestamp('reviewed_at')->nullable();

                $table->foreignId('approved_by')->nullable()->constrained('users');
                $table->timestamp('approved_at')->nullable();

                $table->foreignId('rejected_by')->nullable()->constrained('users');
                $table->timestamp('rejected_at')->nullable();

                $table->text('rejection_reason')->nullable();

                $table->timestamps();
                $table->softDeletes();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('assets');
        Schema::enableForeignKeyConstraints();
    }
};
