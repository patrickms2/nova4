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
        Schema::create('people', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('display_name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('document_type')->nullable();
            $table->string('document_number')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('person_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->string('role')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['person_id', 'role']);
        });

        Schema::create('person_property', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('role')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['person_id', 'property_id', 'role']);
        });

        Schema::table('rental_guests', function (Blueprint $table): void {
            $table->foreignId('person_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
        Schema::table('rental_contacts', function (Blueprint $table): void {
            $table->foreignId('person_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
        Schema::table('rental_reservations', function (Blueprint $table): void {
            $table->foreignId('property_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('person_id')->nullable()->after('guest_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_reservations', fn (Blueprint $table) => $table->dropConstrainedForeignId('person_id'));
        Schema::table('rental_reservations', fn (Blueprint $table) => $table->dropConstrainedForeignId('property_id'));
        Schema::table('rental_contacts', fn (Blueprint $table) => $table->dropConstrainedForeignId('person_id'));
        Schema::table('rental_guests', fn (Blueprint $table) => $table->dropConstrainedForeignId('person_id'));
        Schema::dropIfExists('person_property');
        Schema::dropIfExists('person_roles');
        Schema::dropIfExists('people');
    }
};
