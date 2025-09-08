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
        Schema::create('asset_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->string('asset_tag')->nullable(); // Physical asset tag
            $table->string('asset_name'); // Asset name
            $table->string('serial_number')->nullable(); // Unique serial number
            $table->foreignId('asset_category_id')->constrained('asset_categories');
            $table->foreignId('asset_type_id')->constrained('asset_types');
            $table->foreignId('assignee_id')->nullable()->constrained('assignees')->nullOnDelete();
            $table->enum('status', ['pending_approval', 'in_use', 'maintenance', 'retired', 'disposed','Good', 'Non-functional'])->default('pending_approval');
            $table->double('original_value')->nullable()->default(0); // Original purchase value
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
        Schema::dropIfExists('asset_items');
        Schema::enableForeignKeyConstraints();
    }
};
