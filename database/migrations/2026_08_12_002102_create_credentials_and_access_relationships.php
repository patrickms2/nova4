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
        Schema::create('credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('name');
            $table->string('identifier')->nullable()->unique();
            $table->text('secret')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamp('valid_from')->nullable()->index();
            $table->timestamp('valid_until')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('access_grant_credential', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('access_grant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credential_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['access_grant_id', 'credential_id']);
        });

        Schema::table('access_grants', function (Blueprint $table): void {
            $table->foreignId('person_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->nullableMorphs('source');
            $table->string('status')->default('active')->index();
            $table->json('metadata')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_grants', function (Blueprint $table): void {
            $table->dropMorphs('source');
            $table->dropConstrainedForeignId('person_id');
            $table->dropColumn(['status', 'metadata']);
        });
        Schema::dropIfExists('access_grant_credential');
        Schema::dropIfExists('credentials');
    }
};
