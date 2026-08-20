<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       
        /*Schema::create('community_appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('community_department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable();
            $table->string('status')->default('scheduled')->index();
            $table->string('location')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('community_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('community_department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('normal')->index();
            $table->string('status')->default('open')->index();
            $table->dateTime('due_at')->nullable()->index();
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('community_fees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('concept');
            $table->date('period')->index();
            $table->decimal('amount', 12, 2);
            $table->date('due_date')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->dateTime('paid_at')->nullable();
            $table->string('receipt_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['community_id', 'status', 'period']);
        });
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
        Schema::create('community_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('community_department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->date('attendance_date')->index();
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('checked_out_at')->nullable();
            $table->string('type')->default('presence')->index();
            $table->string('status')->default('recorded')->index();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employee_id', 'attendance_date']);
        });*/
    }

    public function down(): void
    {
        Schema::dropIfExists('community_attendances');
        Schema::dropIfExists('community_shifts');
        Schema::dropIfExists('community_fees');
        Schema::dropIfExists('community_tickets');
        Schema::dropIfExists('community_appointments');
        Schema::dropIfExists('community_department_employee');
        Schema::dropIfExists('community_departments');
        Schema::dropIfExists('community_owner_documents');
        Schema::dropIfExists('community_person');
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('community_id');
            $table->dropColumn('unit_reference');
        });
    }
};
