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
        Schema::create('asset_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->json('meta_data')->nullable(); // Metadata for the document
            $table->string('document_type')->nullable(); // Type of document
            $table->string('file_path')->nullable(); // Path to the document file
            $table->string('file_name')->nullable(); // Original filename
            $table->string('mime_type')->nullable(); // MIME type of the document
            $table->unsignedBigInteger('file_size')->nullable(); // File size in bytes
            $table->text('description')->nullable(); // Document description
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_documents');
    }
};
