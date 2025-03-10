<?php

use App\Http\Middleware\UserStatusMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\AdminMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('login', [AuthController::class, 'login']);

Route::get('validateToken', [AuthController::class, 'validateToken']);
Route::post('recoverPassword', [UserController::class, 'passwordRecovery']);
Route::post('updatePassword', [UserController::class, 'updatePassword']);

Route::get('validateToken', [AuthController::class, 'validateToken']);

Route::prefix('public')->group(function(){
    Route::prefix('tender')->group(function(){
        Route::get('search', [TenderController::class, 'search']);
        Route::get('get-edital/{idLicitacao}', [TenderController::class, 'edital']);
    });
});

Route::middleware(['jwt', UserStatusMiddleware::class])->group(function(){
    Route::post('logout', [AuthController::class, 'logout']);

    Route::prefix('tender')->group(function(){
        Route::get('search', [TenderController::class, 'search']);
        Route::get('get-edital/{idLicitacao}', [TenderController::class, 'edital']);
        Route::post('note', [TenderController::class, 'note']);
        Route::post('favorite/{tender_id}', [TenderController::class, 'favorite']);        
        Route::delete('note-delete/{note_id}', [TenderController::class, 'noteDelete']);
        Route::delete('{tender_id}', [TenderController::class, 'delete']);
    });

    // Open user
    Route::prefix('user')->group(function(){
        Route::get('getUser', [UserController::class, 'getUser']);
        Route::patch('{id}', [UserController::class, 'update']);
        Route::get('/login-as/{userId}', [UserController::class, 'loginAsUser']);
    });

    Route::prefix('filter')->group(function(){
        Route::get('/', [FilterController::class, 'getFilter']);
        Route::post('/', [FilterController::class, 'createOrUpdate']);
    });

    Route::prefix('file')->group(function(){
        Route::get('search', [FileController::class, 'search']);
        Route::post('create', [FileController::class, 'create']);
        Route::patch('{id}', [FileController::class, 'update']);
        Route::delete('{id}', [FileController::class, 'delete']);
    });

    Route::prefix('category')->group(function(){
        Route::get('search', [CategoryController::class, 'search']);
        Route::get('all', [CategoryController::class, 'all']);
    });

    Route::get('setting/search', [SettingController::class, 'search']);

    Route::middleware(AdminMiddleware::class)->group(function(){
        Route::prefix('user')->group(function(){
            Route::get('search', [UserController::class, 'search']);
            Route::post('create', [UserController::class, 'create']);
            Route::post('block/{id}', [UserController::class, 'userBlock']);
            Route::delete('{id}', [UserController::class, 'delete']);
        });

        Route::prefix('category')->group(function(){
            Route::post('create', [CategoryController::class, 'create']);
            Route::patch('{id}', [CategoryController::class, 'update']);
            Route::delete('{id}', [CategoryController::class, 'delete']);
        });

        Route::prefix('dashboard')->group(function(){
            Route::get('search', [DashboardController::class, 'search']);
            Route::get('userGraph', [DashboardController::class, 'userGraph']);
        });

        Route::prefix('automation')->group(function(){
            Route::get('search', [AutomationController::class, 'search']);
            Route::post('create', [AutomationController::class, 'create']);
        });

        Route::patch('setting/update', [SettingController::class, 'update']);
    });
});    


Route::prefix('webhook')->group(function() {
    Route::post('hotmart', [WebhookController::class, 'handle']);
});