<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
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

Route::middleware('jwt', 'user')->group(function(){    
    Route::post('logout', [AuthController::class, 'logout']);


    Route::middleware(AdminMiddleware::class)->group(function(){

        Route::prefix('user')->group(function(){
            Route::get('search', [UserController::class, 'search']);
            Route::post('block/{id}', [UserController::class, 'userBlock']);
        });
    
        Route::prefix('dashboard')->group(function(){
            Route::get('search', [DashboardController::class, 'search']);
        });
    });

});
