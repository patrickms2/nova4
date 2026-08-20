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
        Schema::create('community_document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->boolean('requires_expiration')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['community_id', 'code']);
        });

        Schema::table('community_owner_documents', function (Blueprint $table): void {
            $table->foreignId('community_document_type_id')->nullable()->after('property_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_owner_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('community_document_type_id');
        });

        Schema::dropIfExists('community_document_types');
    }
};
