<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelColumn extends Model
{
    protected $fillable = [
        'model_definition_id',
        'column_name',
        'data_type',
        'nullable',
        'unique',
        'default_value',
        'is_fillable',
        'cast_type',
        'order',
    ];

    protected $casts = [
        'nullable' => 'boolean',
        'unique' => 'boolean',
        'is_fillable' => 'boolean',
        'order' => 'integer',
    ];

    public function modelDefinition()
    {
        return $this->belongsTo(ModelDefinition::class);
    }
}
