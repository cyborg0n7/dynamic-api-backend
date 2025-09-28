<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\OrchestrationJob;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('test:orchestration', function () {
    $this->info('Testing Orchestration Worker...');
    
    // Sample API configuration for testing
    $testApis = [
        [
            'url' => 'https://jsonplaceholder.typicode.com/posts/1',
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json']
        ],
        [
            'url' => 'https://httpbin.org/delay/2',
            'method' => 'GET',
            'conditions' => [
                ['type' => 'success', 'api_index' => 0]
            ]
        ]
    ];
    
    // Dispatch the orchestration job
    $orchestrationId = 'test_' . uniqid();
    OrchestrationJob::dispatch('test-user', $testApis, $orchestrationId, ['stop_on_failure' => false]);
    
    $this->info("Orchestration job dispatched with ID: {$orchestrationId}");
    $this->info("Check logs and cache for results in a few seconds.");
    
})->purpose('Test the orchestration worker with sample APIs');
