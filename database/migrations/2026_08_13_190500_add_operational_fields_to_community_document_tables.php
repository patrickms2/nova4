<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('community_document_types', 'community_id')) {
            Schema::table('community_document_types', function (Blueprint $table): void {
                $table->foreignId('community_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code');
                $table->text('description')->nullable();
                $table->boolean('requires_expiration')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->unique(['community_id', 'code']);
            });
        }

        if (! Schema::hasColumn('community_document_imports', 'community_id')) {
            Schema::table('community_document_imports', function (Blueprint $table): void {
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
            });
        }

        if (! Schema::hasColumn('community_owner_documents', 'community_document_type_id')) {
            Schema::table('community_owner_documents', function (Blueprint $table): void {
                $table->foreignId('community_document_type_id')->nullable()->after('property_id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('community_owner_documents', 'community_document_type_id')) {
            Schema::table('community_owner_documents', fn (Blueprint $table) => $table->dropConstrainedForeignId('community_document_type_id'));
        }

        Schema::dropIfExists('community_document_imports');
        Schema::dropIfExists('community_document_types');
    }
};
