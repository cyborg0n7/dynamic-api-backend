<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiDefinition extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'endpoint',
        'method',
        'target_url',
        'auth_type',
        'transformation_rules',
        'api_key',
    ];
}
