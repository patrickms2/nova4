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
        Schema::table('facturas', function (Blueprint $table) {
            if (! Schema::hasColumn('facturas', 'verifactu_status')) {
                $table->string('verifactu_status', 30)->nullable()->after('meta');
            }
            if (! Schema::hasColumn('facturas', 'verifactu_hash')) {
                $table->string('verifactu_hash', 128)->nullable()->after('verifactu_status');
            }
            if (! Schema::hasColumn('facturas', 'verifactu_previous_hash')) {
                $table->string('verifactu_previous_hash', 128)->nullable()->after('verifactu_hash');
            }
            if (! Schema::hasColumn('facturas', 'verifactu_response_code')) {
                $table->string('verifactu_response_code', 30)->nullable()->after('verifactu_previous_hash');
            }
            if (! Schema::hasColumn('facturas', 'verifactu_response_message')) {
                $table->text('verifactu_response_message')->nullable()->after('verifactu_response_code');
            }
            if (! Schema::hasColumn('facturas', 'verifactu_registration_date')) {
                $table->dateTime('verifactu_registration_date')->nullable()->after('verifactu_response_message');
            }
            if (! Schema::hasColumn('facturas', 'verifactu_csv')) {
                $table->string('verifactu_csv', 100)->nullable()->after('verifactu_registration_date');
            }
            if (! Schema::hasColumn('facturas', 'verifactu_qr_url')) {
                $table->text('verifactu_qr_url')->nullable()->after('verifactu_csv');
            }
            if (! Schema::hasColumn('facturas', 'verifactu_sent_at')) {
                $table->dateTime('verifactu_sent_at')->nullable()->after('verifactu_qr_url');
            }
            if (! Schema::hasColumn('facturas', 'verifactu_request_xml')) {
                $table->longText('verifactu_request_xml')->nullable()->after('verifactu_sent_at');
            }
            if (! Schema::hasColumn('facturas', 'verifactu_response_xml')) {
                $table->longText('verifactu_response_xml')->nullable()->after('verifactu_request_xml');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $columns = [
                'verifactu_status',
                'verifactu_hash',
                'verifactu_previous_hash',
                'verifactu_response_code',
                'verifactu_response_message',
                'verifactu_registration_date',
                'verifactu_csv',
                'verifactu_qr_url',
                'verifactu_sent_at',
                'verifactu_request_xml',
                'verifactu_response_xml',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('facturas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
