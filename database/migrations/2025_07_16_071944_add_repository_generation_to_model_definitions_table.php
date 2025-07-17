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
        Schema::table('model_definitions', function (Blueprint $table) {
            // Repository generation toggle
            $table->boolean('generate_repository')->default(false)->after('generate_form_request');

            // Editable repository names
            $table->string('repository_name')->nullable()->after('form_request_name');
            $table->string('repository_interface_name')->nullable()->after('repository_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_definitions', function (Blueprint $table) {
            // Drop repository fields
            $table->dropColumn([
                'repository_interface_name',
                'repository_name',
                'generate_repository',
            ]);
        });
    }
};
