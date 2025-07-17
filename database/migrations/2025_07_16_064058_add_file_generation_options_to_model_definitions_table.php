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
            // File generation toggles
            $table->boolean('generate_factory')->default(true)->after('has_soft_deletes');
            $table->boolean('generate_policy')->default(true)->after('generate_factory');
            $table->boolean('generate_resource_controller')->default(true)->after('generate_policy');
            $table->boolean('generate_json_resource')->default(false)->after('generate_resource_controller');
            $table->boolean('generate_api_controller')->default(false)->after('generate_json_resource');
            $table->boolean('generate_form_request')->default(false)->after('generate_api_controller');

            // Editable file names
            $table->string('factory_name')->nullable()->after('generate_form_request');
            $table->string('policy_name')->nullable()->after('factory_name');
            $table->string('resource_controller_name')->nullable()->after('policy_name');
            $table->string('json_resource_name')->nullable()->after('resource_controller_name');
            $table->string('api_controller_name')->nullable()->after('json_resource_name');
            $table->string('form_request_name')->nullable()->after('api_controller_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_definitions', function (Blueprint $table) {
            // Drop editable file names
            $table->dropColumn([
                'form_request_name',
                'api_controller_name',
                'json_resource_name',
                'resource_controller_name',
                'policy_name',
                'factory_name',
            ]);

            // Drop file generation toggles
            $table->dropColumn([
                'generate_form_request',
                'generate_api_controller',
                'generate_json_resource',
                'generate_resource_controller',
                'generate_policy',
                'generate_factory',
            ]);
        });
    }
};
