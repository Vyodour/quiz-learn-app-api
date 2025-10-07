<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\LearningPathController;
use Illuminate\Http\Request;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('learning-paths')->controller(LearningPathController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{learningPath}', 'show');
});

Route::prefix('modules')->controller(ModuleController::class)->group(function () {
        Route::get('/', 'indexOrphan');
    });

Route::prefix('learning-paths/{learningPath}/modules')->controller(ModuleController::class)->group(function () {
        Route::get('/', 'index');
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'user' => $user,
            'roles' => $user->getRoleNames(),
        ]);
    });

    Route::middleware('role:admin')->group(function () {

        Route::prefix('learning-paths')->controller(LearningPathController::class)->group(function () {
            Route::post('/', 'store');
            Route::patch('/{learningPath}', 'update');
            Route::delete('/{learningPath}', 'destroy');
        });

         Route::prefix('modules')->controller(ModuleController::class)->group(function () {
            Route::post('/', 'store');
            Route::patch('/{module}', 'update');
            Route::delete('/{module}', 'destroy');
        });
    });
});
