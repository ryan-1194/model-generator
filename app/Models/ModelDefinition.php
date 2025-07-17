<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelDefinition extends Model
{
    protected $fillable = [
        'model_name',
        'table_name',
        'generate_migration',
        'has_timestamps',
        'has_soft_deletes',
        'generate_factory',
        'generate_policy',
        'generate_resource_controller',
        'generate_json_resource',
        'generate_api_controller',
        'generate_form_request',
        'generate_repository',
        'factory_name',
        'policy_name',
        'resource_controller_name',
        'json_resource_name',
        'api_controller_name',
        'form_request_name',
        'repository_name',
        'repository_interface_name',
    ];

    protected $casts = [
        'generate_migration' => 'boolean',
        'has_timestamps' => 'boolean',
        'has_soft_deletes' => 'boolean',
        'generate_factory' => 'boolean',
        'generate_policy' => 'boolean',
        'generate_resource_controller' => 'boolean',
        'generate_json_resource' => 'boolean',
        'generate_api_controller' => 'boolean',
        'generate_form_request' => 'boolean',
        'generate_repository' => 'boolean',
    ];

    public function columns()
    {
        return $this->hasMany(ModelColumn::class)->orderBy('order');
    }
}
