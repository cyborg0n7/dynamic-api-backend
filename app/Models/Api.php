<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Api extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'endpoint',
        'method',
        'auth_type',
        'transformation_rules',
         'api_key'
    ];
    public function transformations()
{
    return $this->hasMany(ApiTransformation::class);
}

}
