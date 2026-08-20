<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builder_schemas', function (Blueprint $table) {
            $table->id();
            $table->string('model');
            $table->json('schema');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_schemas');
    }
};
