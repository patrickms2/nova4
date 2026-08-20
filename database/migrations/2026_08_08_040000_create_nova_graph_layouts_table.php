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
        Schema::create('nova_graph_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('workspace_id', 191);
            $table->string('node_address', 191);
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->timestamps();

            $table->unique(['workspace_id', 'node_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nova_graph_layouts');
    }
};
