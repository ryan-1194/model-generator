<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Post extends Model
{

    protected $fillable = ['name', 'description'];


    protected $casts = [
        'name' => 'string',
        'description' => 'string'
    ];



}
