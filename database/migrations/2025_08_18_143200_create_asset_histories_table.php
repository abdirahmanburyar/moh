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
        Schema::create('asset_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_item_id')->constrained('asset_items')->onDelete('cascade');
            $table->string('action');
            $table->string('action_type')->nullable(); // 'status_change', 'transfer', 'retirement', 'approval'
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->nullable();
            $table->foreignId('approval_id')->nullable()->constrained('asset_approvals')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('assignees')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_histories');
    }
};
