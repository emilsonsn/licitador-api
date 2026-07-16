<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\ProposalCatalogController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProposalTrackingController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\UserStatusMiddleware;
use Illuminate\Support\Facades\Route;

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

// Route::prefix('public')->group(function(){
//     Route::prefix('tender')->group(function(){
//         Route::get('search', [TenderController::class, 'search']);
//         Route::get('get-edital/{idLicitacao}', [TenderController::class, 'edital']);
//     });
// });

Route::middleware(['jwt', UserStatusMiddleware::class])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::prefix('tender')->group(function () {
        Route::get('search', [TenderController::class, 'search']);
        Route::get('get-edital/{idLicitacao}', [TenderController::class, 'edital']);
        Route::get('{tender_id}/items', [TenderController::class, 'items']);
        Route::post('note', [TenderController::class, 'note']);
        Route::post('favorite/{tender_id}', [TenderController::class, 'favorite']);
        Route::get('calendar', [TenderController::class, 'calendar']);
        Route::post('calendar/{tender_id}', [TenderController::class, 'calendarToggle']);
        Route::delete('note-delete/{note_id}', [TenderController::class, 'noteDelete']);
        Route::delete('{tender_id}', [TenderController::class, 'delete']);
    });

    // Open user
    Route::prefix('user')->group(function () {
        Route::get('getUser', [UserController::class, 'getUser']);
        Route::patch('{id}', [UserController::class, 'update']);
        Route::get('/login-as/{userId}', [UserController::class, 'loginAsUser']);
    });

    Route::prefix('filter')->group(function () {
        Route::get('/', [FilterController::class, 'getFilter']);
        Route::post('/', [FilterController::class, 'createOrUpdate']);
    });

    Route::prefix('company')->group(function () {
        Route::get('/', [CompanyController::class, 'getCompany']);
        Route::post('/', [CompanyController::class, 'createOrUpdate']);
    });

    Route::prefix('proposal')->group(function () {
        Route::get('/', [ProposalController::class, 'search']);
        Route::post('fill', [ProposalController::class, 'fill']);
        Route::post('/', [ProposalController::class, 'create']);
        Route::get('{id}', [ProposalController::class, 'get']);
        Route::patch('{id}', [ProposalController::class, 'update']);
        Route::delete('{id}', [ProposalController::class, 'delete']);
        Route::get('{id}/view', [ProposalController::class, 'view']);
        Route::get('{proposalId}/tracking', [ProposalTrackingController::class, 'get']);
        Route::put('{proposalId}/tracking', [ProposalTrackingController::class, 'update']);
        Route::post('{proposalId}/tracking/apply-discount', [ProposalTrackingController::class, 'applyDiscount']);
        Route::post('{proposalId}/tracking/finish', [ProposalTrackingController::class, 'finish']);
        Route::post('{proposalId}/tracking/reopen', [ProposalTrackingController::class, 'reopen']);
        Route::get('{proposalId}/tracking/print', [ProposalTrackingController::class, 'print']);
        Route::get('{proposalId}/tracking/export', [ProposalTrackingController::class, 'export']);
        Route::get('{proposalId}/catalog', [ProposalCatalogController::class, 'get']);
        Route::put('{proposalId}/catalog', [ProposalCatalogController::class, 'update']);
        Route::post('{proposalId}/catalog/generate', [ProposalCatalogController::class, 'generate']);
        Route::post('{proposalId}/catalog/items/{itemId}/image', [ProposalCatalogController::class, 'uploadImage']);
        Route::delete('{proposalId}/catalog/items/{itemId}/image', [ProposalCatalogController::class, 'deleteImage']);
    });

    Route::get('proposal-catalog/{catalogId}/view', [ProposalCatalogController::class, 'view']);

    Route::prefix('file')->group(function () {
        Route::get('search', [FileController::class, 'search']);
        Route::post('create', [FileController::class, 'create']);
        Route::patch('{id}', [FileController::class, 'update']);
        Route::delete('{id}', [FileController::class, 'delete']);
    });

    Route::prefix('category')->group(function () {
        Route::get('search', [CategoryController::class, 'search']);
        Route::get('all', [CategoryController::class, 'all']);
    });

    Route::get('setting/search', [SettingController::class, 'search']);

    Route::middleware(AdminMiddleware::class)->group(function () {
        Route::prefix('user')->group(function () {
            Route::get('search', [UserController::class, 'search']);
            Route::post('create', [UserController::class, 'create']);
            Route::post('block/{id}', [UserController::class, 'userBlock']);
            Route::delete('{id}', [UserController::class, 'delete']);
        });

        Route::prefix('category')->group(function () {
            Route::post('create', [CategoryController::class, 'create']);
            Route::patch('{id}', [CategoryController::class, 'update']);
            Route::delete('{id}', [CategoryController::class, 'delete']);
        });

        Route::prefix('dashboard')->group(function () {
            Route::get('search', [DashboardController::class, 'search']);
            Route::get('indicators', [DashboardController::class, 'indicators']);
            Route::get('userGraph', [DashboardController::class, 'userGraph']);
        });

        Route::prefix('automation')->group(function () {
            Route::get('search', [AutomationController::class, 'search']);
            Route::post('create', [AutomationController::class, 'create']);
        });

        Route::patch('setting/update', [SettingController::class, 'update']);
    });
});

Route::prefix('webhook')->group(function () {
    Route::post('hotmart', [WebhookController::class, 'handle']);
});
