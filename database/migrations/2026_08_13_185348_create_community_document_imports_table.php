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
        Schema::create('community_document_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('community_document_type_id')->nullable()->index();
            $table->string('original_name');
            $table->string('source_path');
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('files_found')->default(0);
            $table->unsignedInteger('documents_created')->default(0);
            $table->unsignedInteger('unmatched_files')->default(0);
            $table->json('issues')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_document_imports');
    }
};
