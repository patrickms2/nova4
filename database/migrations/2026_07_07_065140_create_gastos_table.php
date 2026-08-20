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
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('codigo')->nullable()->unique();
            $table->string('descripcion')->nullable();
            $table->text('notas')->nullable();
            $table->string('categoria')->nullable()->index();
            $table->string('type')->default('expense')->index();
            $table->timestamp('fecha')->nullable()->useCurrent();
            $table->decimal('base_imponible', 12, 2)->default(0.00);
            $table->decimal('impuesto', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2)->default(0.00);
            $table->string('estado')->default('pendiente')->index();
            $table->string('metodo_pago')->nullable();
            $table->string('documento')->nullable();
            $table->boolean('deducible')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
