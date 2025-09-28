<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrchestrationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_id',
        'rule_name',
        'condition',
        'action'
    ];
    protected $casts = [
    'condition' => 'array',
    'action' => 'array',
];

}
