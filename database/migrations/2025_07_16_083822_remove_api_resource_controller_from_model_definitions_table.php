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
            // Drop API Resource Controller fields
            $table->dropColumn([
                'api_resource_controller_name',
                'generate_api_resource_controller',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_definitions', function (Blueprint $table) {
            // Re-add API Resource Controller fields
            $table->boolean('generate_api_resource_controller')->default(false)->after('generate_api_controller');
            $table->string('api_resource_controller_name')->nullable()->after('api_controller_name');
        });
    }
};
