<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
         'api_id',
        'endpoint',
        'status',
        'latency',
        'status_code',
        'request_payload',
        'response_payload'
    ];
     protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];
}