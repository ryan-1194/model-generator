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
        Schema::create('model_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_definition_id')->constrained()->onDelete('cascade');
            $table->string('column_name');
            $table->string('data_type'); // string, text, integer, boolean, date, etc.
            $table->boolean('nullable')->default(false);
            $table->boolean('unique')->default(false);
            $table->string('default_value')->nullable();
            $table->boolean('is_fillable')->default(true);
            $table->string('cast_type')->nullable(); // string, integer, boolean, array, etc.
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_columns');
    }
};
