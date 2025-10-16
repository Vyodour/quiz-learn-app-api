<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\ContentUnitController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\CodeChallenge\CodeChallengeController;
use App\Http\Controllers\CodeChallenge\AdminCodeChallengeController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\AdminPlanController;
use Illuminate\Http\Request;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/midtrans-webhook', [WebhookController::class, 'midtransHandler']);

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

    Route::get('/contents/{content}', [ContentController::class, 'show']);

    Route::prefix('contents/{content}/units')->controller(ContentUnitController::class)->group(function () {
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

    Route::prefix('quizzes/{quizInformation}')->group(function () {
        Route::get('questions', [QuizQuestionController::class, 'index'])
            ->name('questions.index');
    });

    Route::prefix('/code-challenges/{challenge}')->group(function () {
        Route::get('/', [CodeChallengeController::class, 'show']);
        Route::post('/submit', [CodeChallengeController::class, 'submit']);
    });

    Route::prefix('progress/units/{contentUnitOrder}')->controller(UserProgressController::class)->group(function () {
        Route::post('/complete', 'completeUnit');
        Route::post('/reset', 'resetUnit');
    });

    Route::resource('plans', AdminPlanController::class)->only(['index', 'show']);

    Route::post('/purchase', [PurchaseController::class, 'store']);

    //Route::get('/my-submmissions', [UserCodeSubmissionController::class, 'index']);

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

        Route::post('/modules/{module}/contents', [ContentController::class, 'store']);

        Route::prefix('contents')->controller(ContentController::class)->group(function () {
            Route::patch('/{content}', 'update');
            Route::delete('/{content}', 'destroy');
        });

        Route::prefix('contents/{content}/units')->controller(ContentUnitController::class)->group(function () {
            Route::post('/', 'store');
        });


        Route::prefix('content-units')->controller(ContentUnitController::class)->group(function () {
            Route::patch('/{contentUnitOrder}', 'update');
            Route::delete('/{contentUnitOrder}', 'destroy');
        });

        Route::prefix('quizzes/{quizInformation}')->group(function () {
            Route::prefix('questions')->group(function () {
                Route::post('/', [QuizQuestionController::class, 'store'])->name('questions.store');
                Route::patch('/{quizQuestion}', [QuizQuestionController::class, 'update'])->name('questions.update');
                Route::patch('/{quizQuestion}/options', [QuizQuestionController::class, 'updateOption'])->name('questions.updateOption');
                Route::delete('/{quizQuestion}', [QuizQuestionController::class, 'destroy'])->name('questions.destroy');
            });
        });
        Route::prefix('admin')->group(function () {
            Route::resource('challenges', AdminCodeChallengeController::class)->except(['show']);
            Route::prefix('submissions')->group(function () {
                Route::get('/', [AdminCodeChallengeController::class, 'allSubmissions']);
                Route::get('/{submission}', [AdminCodeChallengeController::class, 'showSubmission']);
            });

        });

        Route::resource('plans', AdminPlanController::class)->except(['index', 'show']);

    });
});
