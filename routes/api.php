<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
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

Route::middleware('jwt')->group(function(){
    Route::post('logout', [AuthController::class, 'logout']);

    Route::prefix('tender')->group(function(){
        Route::get('search', [TenderController::class, 'search']);
        Route::get('get-edital/{idLicitacao}', [TenderController::class, 'edital']);
        Route::post('favorite/{tender_id}', [TenderController::class, 'favorite']);
    });

    Route::get('user/getUser', [UserController::class, 'getUser']);

    Route::get('setting/search', [SettingController::class, 'search']);

    Route::middleware(AdminMiddleware::class)->group(function(){

        Route::prefix('user')->group(function(){
            Route::get('search', [UserController::class, 'search']);
            Route::post('create', [UserController::class, 'create']);
            Route::patch('{id}', [UserController::class, 'update']);
            Route::post('block/{id}', [UserController::class, 'userBlock']);
        });

        Route::prefix('dashboard')->group(function(){
            Route::get('search', [DashboardController::class, 'search']);
            Route::get('userGraph', [DashboardController::class, 'userGraph']);
        });

        Route::patch('setting/update', [SettingController::class, 'update']);

    });
});

Route::prefix('webhook')->group(function() {
    Route::post('hotmart', [WebhookController::class, 'handle']);
});
