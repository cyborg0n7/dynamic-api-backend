<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\OrchestrationRuleController;
use App\Http\Controllers\RequestLogController;
use App\Http\Controllers\BillingRecordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\SecureController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\AuditorController;
use App\Http\Controllers\ApiGatewayController;
use App\Http\Controllers\SimpleApiGatewayController;
use App\Http\Controllers\OrchestrationController;

// ----------------------
// Public Routes
// ----------------------

// Ping test route
Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

// Orchestration Routes
Route::get('/orchestrate/{id}/status', [OrchestrationController::class, 'status'])->name('orchestration.status');
Route::delete('/orchestrate/{id}/cancel', [OrchestrationController::class, 'cancel'])->name('orchestration.cancel');
Route::post('/orchestrate', [OrchestrationController::class, 'run']);

// User registration
Route::post('/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'role' => 'nullable|string|in:admin,developer,auditor'
    ]);

    $user = \App\Models\User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
        'role' => $request->role ?? 'developer',
    ]);

    return response()->json(['message' => 'User registered successfully', 'user' => $user]);
});

// Login
Route::post('/login', [LoginController::class, 'login'])->name('login');

// ----------------------
// Authenticated Routes (JWT)
// ----------------------
Route::middleware('auth:api')->group(function () {

    // Profile
    Route::get('/profile', function () {
        return auth()->user();
    });

    // Logout
    Route::post('/logout', [LoginController::class, 'logout']);

    // Refresh JWT
    Route::post('/refresh', [LoginController::class, 'refresh']);

    // Create API key
    Route::post('/apikey/create', [ApiKeyController::class, 'create']);

    // ----------------------
    // CRUD Routes — Protected
    // ----------------------
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    Route::prefix('apis')->group(function () {
        Route::get('/', [ApiController::class, 'index']);
        Route::post('/', [ApiController::class, 'store']);
        Route::get('/{id}', [ApiController::class, 'show']);
        Route::put('/{id}', [ApiController::class, 'update']);
        Route::delete('/{id}', [ApiController::class, 'destroy']);
    });

    Route::prefix('rules')->group(function () {
        Route::get('/', [OrchestrationRuleController::class, 'index']);
        Route::post('/', [OrchestrationRuleController::class, 'store']);
        Route::get('/{id}', [OrchestrationRuleController::class, 'show']);
        Route::put('/{id}', [OrchestrationRuleController::class, 'update']);
        Route::delete('/{id}', [OrchestrationRuleController::class, 'destroy']);
    });

    Route::prefix('logs')->group(function () {
        Route::get('/', [RequestLogController::class, 'index']);
        Route::post('/', [RequestLogController::class, 'store']);
        Route::get('/{id}', [RequestLogController::class, 'show']);
        Route::delete('/{id}', [RequestLogController::class, 'destroy']);
    });

    Route::prefix('billing')->group(function () {
        Route::get('/', [BillingRecordController::class, 'index']);
        Route::post('/', [BillingRecordController::class, 'store']);
        Route::get('/{id}', [BillingRecordController::class, 'show']);
        Route::delete('/{id}', [BillingRecordController::class, 'destroy']);
    });

    // ----------------------
    // Role-Based Routes
    // ----------------------
    
    // Admin Dashboard
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])
        ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':admin');

    // Developer Dashboard
    Route::get('/developer/dashboard', [DeveloperController::class, 'dashboard'])
        ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':developer');

    // Auditor Reports
    Route::get('/auditor/reports', [AuditorController::class, 'reports'])
        ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':auditor');

});

// ----------------------
// API Key Protected Routes
// ----------------------
Route::middleware(['apikey'])->group(function () {
    Route::get('/secure-data', [SecureController::class, 'index']);
});

Route::get('/apis', [ApiController::class, 'index']);

// ----------------------
// API Gateway Catch-All Route (must be last)
// ----------------------
Route::any('/{any}', [SimpleApiGatewayController::class, 'handle'])
    ->where('any', '.*');
