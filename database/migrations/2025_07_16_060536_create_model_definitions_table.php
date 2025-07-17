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
        Schema::create('model_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('model_name');
            $table->string('table_name')->nullable();
            $table->boolean('generate_migration')->default(true);
            $table->boolean('has_timestamps')->default(true);
            $table->boolean('has_soft_deletes')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_definitions');
    }
};
