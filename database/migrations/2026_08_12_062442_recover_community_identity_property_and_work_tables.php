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
        if (! Schema::hasTable('communities')) {
            Schema::create('communities', function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('address')->nullable();
                $table->string('contact_name')->nullable();
                $table->string('contact_phone')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('active')->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('people')) {
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
        }

        if (! Schema::hasTable('properties')) {
            Schema::create('properties', function (Blueprint $table): void {
                $table->id();
                $table->string('slug')->unique();
                $table->foreignId('community_id')->nullable()->constrained()->nullOnDelete();
                $table->string('unit_reference')->nullable()->index();
                $table->string('name');
                $table->text('address')->nullable();
                $table->string('timezone')->default('Atlantic/Canary');
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        } else {
            if (! Schema::hasColumn('properties', 'community_id')) {
                Schema::table('properties', fn (Blueprint $table) => $table->foreignId('community_id')->nullable()->constrained()->nullOnDelete());
            }

            if (! Schema::hasColumn('properties', 'unit_reference')) {
                Schema::table('properties', fn (Blueprint $table) => $table->string('unit_reference')->nullable()->index());
            }
        }

        if (! Schema::hasTable('person_roles')) {
            Schema::create('person_roles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('person_id')->constrained()->cascadeOnDelete();
                $table->string('role')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['person_id', 'role']);
            });
        }

        if (! Schema::hasTable('person_property')) {
            Schema::create('person_property', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('person_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->constrained()->cascadeOnDelete();
                $table->string('role')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['person_id', 'property_id', 'role']);
            });
        }

        if (! Schema::hasTable('community_person')) {
            Schema::create('community_person', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('community_id')->constrained()->cascadeOnDelete();
                $table->foreignId('person_id')->constrained()->cascadeOnDelete();
                $table->string('role')->index();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['community_id', 'person_id', 'role']);
            });
        }

        if (! Schema::hasTable('community_departments')) {
            Schema::create('community_departments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('community_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('color')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('community_department_employee')) {
            Schema::create('community_department_employee', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('community_department_id')->constrained()->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['community_department_id', 'employee_id'], 'department_employee_unique');
            });
        }

        if (! Schema::hasTable('community_document_types')) {
            Schema::create('community_document_types', function (Blueprint $table): void {
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
        }

        if (! Schema::hasTable('community_owner_documents')) {
            Schema::create('community_owner_documents', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('community_id')->constrained()->cascadeOnDelete();
                $table->foreignId('person_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('community_document_type_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type')->default('document')->index();
                $table->string('title');
                $table->string('path');
                $table->string('status')->default('active')->index();
                $table->date('document_date')->nullable()->index();
                $table->date('expires_at')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['community_id', 'person_id', 'status'], 'owner_documents_context_index');
            });
        }

        if (! Schema::hasTable('community_document_imports')) {
            Schema::create('community_document_imports', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('community_id')->constrained()->cascadeOnDelete();
                $table->foreignId('community_document_type_id')->nullable()->constrained()->nullOnDelete();
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

        if (! Schema::hasTable('community_employee_documents')) {
            Schema::create('community_employee_documents', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('community_id')->constrained()->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('path');
                $table->string('filename');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->string('status')->default('active')->index();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['employee_id', 'status']);
                $table->index(['community_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('community_tickets')) {
            Schema::create('community_tickets', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('community_id')->constrained()->cascadeOnDelete();
                $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('community_department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('work_category_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('work_catalog_id')->nullable()->constrained('work_catalog')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('type')->default('general')->index();
                $table->decimal('amount', 12, 2)->nullable();
                $table->string('attachment_path')->nullable();
                $table->string('priority')->default('normal')->index();
                $table->string('status')->default('open')->index();
                $table->dateTime('due_at')->nullable()->index();
                $table->dateTime('resolved_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['community_id', 'type', 'status']);
            });
        }

        if (! Schema::hasTable('community_shifts')) {
            Schema::create('community_shifts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('community_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('community_department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
                $table->date('shift_date')->index();
                $table->time('starts_at');
                $table->time('ends_at');
                $table->string('status')->default('planned')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['employee_id', 'shift_date']);
            });
        }

        if (! Schema::hasTable('community_attendance_community') && Schema::hasTable('community_attendances')) {
            Schema::create('community_attendance_community', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('community_attendance_id')->constrained()->cascadeOnDelete();
                $table->foreignId('community_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['community_attendance_id', 'community_id'], 'attendance_community_unique');
            });
        }

        if (! Schema::hasTable('work_sessions')) {
            Schema::create('work_sessions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('property_id')->constrained()->cascadeOnDelete();
                $table->foreignId('access_grant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('access_point_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('status');
                $table->timestamp('started_at');
                $table->timestamp('finish_requested_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'status']);
                $table->index(['property_id', 'status']);
            });
        }

        if (! Schema::hasTable('work_reports')) {
            Schema::create('work_reports', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('work_session_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('voice_path')->nullable();
                $table->text('voice_transcription')->nullable();
                $table->text('summary')->nullable();
                $table->json('photos')->nullable();
                $table->json('ai_metadata')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recovery migrations intentionally do not remove restored structures.
    }
};
