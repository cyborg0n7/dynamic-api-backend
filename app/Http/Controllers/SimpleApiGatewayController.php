<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiDefinition;
use Illuminate\Support\Facades\Http;

class SimpleApiGatewayController extends Controller
{
    public function handle(Request $request)
    {
        $path = $request->path();
        $method = $request->method();

        $api = ApiDefinition::where('endpoint', $path)
            ->where('method', $method)
            ->first();

        if (!$api) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'API not found'
                ]
            ], 404);
        }

        // Simple forward request
        try {
            $response = Http::get($api->target_url);
            return response()->json($response->json(), $response->status());
        } catch (Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 500,
                    'message' => 'Gateway error: ' . $e->getMessage()
                ]
            ], 500);
        }
    }
}
