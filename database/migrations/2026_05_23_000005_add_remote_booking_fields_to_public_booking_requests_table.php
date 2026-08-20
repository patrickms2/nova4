<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('public_booking_requests')) {
            Schema::create('public_booking_requests', function (Blueprint $table): void {
                $table->id();
                $table->string('request_reference')->unique();
                $table->string('type');
                $table->unsignedBigInteger('service_id');
                $table->string('service_name');
                $table->unsignedBigInteger('assigned_admin_id')->nullable();
                $table->string('assignment_source')->default('unassigned');
                $table->string('customer_name');
                $table->string('customer_email')->nullable();
                $table->string('customer_phone')->nullable();
                $table->string('status')->default('pending');
                $table->unsignedTinyInteger('guests')->nullable();
                $table->unsignedTinyInteger('rooms')->nullable();
                $table->unsignedTinyInteger('passengers')->nullable();
                $table->unsignedTinyInteger('participants')->nullable();
                $table->date('check_in_date')->nullable();
                $table->date('check_out_date')->nullable();
                $table->date('reservation_date')->nullable();
                $table->time('reservation_time')->nullable();
                $table->dateTime('pickup_date_time')->nullable();
                $table->date('tour_date')->nullable();
                $table->time('tour_schedule')->nullable();
                $table->string('pickup_address')->nullable();
                $table->string('dropoff_address')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->unsignedBigInteger('decided_by_admin_id')->nullable();
                $table->text('decision_notes')->nullable();
                $this->remoteBookingColumns($table);
                $table->timestamps();

                $table->index(['type', 'status']);
                $table->index(['service_id', 'type']);
                $table->index('assigned_admin_id');
            });

            return;
        }

        Schema::table('public_booking_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('public_booking_requests', 'remote_booking_status')) {
                $this->remoteBookingColumns($table);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('public_booking_requests')) {
            return;
        }

        Schema::table('public_booking_requests', function (Blueprint $table): void {
            foreach ([
                'remote_error',
                'remote_response',
                'remote_external_id',
                'remote_source_label',
                'remote_source_platform',
                'remote_booking_status',
            ] as $column) {
                if (Schema::hasColumn('public_booking_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function remoteBookingColumns(Blueprint $table): void
    {
        $table->string('remote_booking_status')->nullable();
        $table->string('remote_source_platform')->nullable();
        $table->string('remote_source_label')->nullable();
        $table->string('remote_external_id')->nullable();
        $table->json('remote_response')->nullable();
        $table->text('remote_error')->nullable();
    }
};
