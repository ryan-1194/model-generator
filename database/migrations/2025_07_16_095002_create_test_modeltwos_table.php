<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_modeltwos', function (Blueprint $table) {
            $table->id();
            $table->string('fdfdfdf');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_modeltwos');
    }
};