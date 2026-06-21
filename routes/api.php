<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;

use App\Http\Controllers\Api\Building\RoomController;
use App\Http\Controllers\Api\Building\RuleController;
use App\Http\Controllers\Api\Entertainment\Football\FootballController;
use App\Http\Controllers\Api\User\AnnouncementController;
use App\Http\Controllers\Api\User\AuthController as UserAuthController;
use App\Http\Controllers\Api\User\CommentController;
use App\Http\Controllers\Api\User\CriticalRemarkController;
use App\Http\Controllers\Api\User\LatePaymentReasonController;
use App\Http\Controllers\Api\User\PaymentController;
use App\Http\Controllers\Api\User\PaymentMethodController;
use App\Http\Controllers\Api\User\UserController;

/*
|--------------------------------------------------------------------------
| API HEALTH CHECK
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => '🚀 API is working!',
        'timestamp' => now()->toIso8601String(),
        'version' => App::version(),
        'environment' => app()->environment(),
        'php_version' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (NO AUTH)
|--------------------------------------------------------------------------
*/

Route::post('user/create', [UserController::class, "store"]);
Route::post('payment/callback', [PaymentController::class, 'callback']);
Route::post('user/auth', [UserAuthController::class, 'login']);
Route::post('user/forgot-password', [UserAuthController::class, 'forgotPassword']);
Route::post('user/reset-password', [UserAuthController::class, 'resetPassword']);

/*
|--------------------------------------------------------------------------
| FOOTBALL PUBLIC ROUTES (NO AUTH)
|--------------------------------------------------------------------------
*/

Route::prefix('football')->group(function () {
    Route::get('/live', [FootballController::class, 'live']);
    Route::get('/fixtures', [FootballController::class, 'fixtures']);
    Route::get('/standings', [FootballController::class, 'standings']);
    Route::get('/leagues', [FootballController::class, 'leagues']);
    Route::get('/match/{fixtureId}', [FootballController::class, 'match']);
    Route::get('/team/{teamId}', [FootballController::class, 'team']);
    Route::get('/scorers', [FootballController::class, 'scorers']);
    Route::get('/test', [FootballController::class, 'testConnection']);
});

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (SANCTUM AUTH)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // USERS
    Route::get("user/fetch", [UserController::class, "index"]);
    Route::get("user/show/{id}", [UserController::class, "show"]);
    Route::patch("user/update/{id}", [UserController::class, "update"]);
    Route::patch('user/update/phone/{id}', [UserController::class, 'updatePhoneNumber']);
    Route::delete("user/delete/{id}", [UserController::class, "destroy"]);
    Route::post('user/logout', [UserAuthController::class, 'logout']);

    // RULES
    Route::get('rule/fetch', [RuleController::class, 'index']);
    Route::get('rule/show/{id}', [RuleController::class, 'show']);
    Route::post('rule/create', [RuleController::class, 'store']);
    Route::patch('rule/update/{id}', [RuleController::class, 'update']);
    Route::delete('rule/delete/{id}', [RuleController::class, 'destroy']);

    // ROOMS
    Route::get('room/fetch', [RoomController::class, 'index']);
    Route::get('room/show/{id}', [RoomController::class, 'show']);
    Route::post('room/create', [RoomController::class, 'store']);
    Route::patch('room/update/{id}', [RoomController::class, 'update']);
    Route::patch('room/update/status/{id}', [RoomController::class, 'updateRoomStatus']);
    Route::delete('room/delete/{id}', [RoomController::class, 'destroy']);

    // PAYMENTS
    Route::get('payment/fetch', [PaymentController::class, 'index']);
    Route::get('payment/show/{id}', [PaymentController::class, 'show']);
    Route::post('payment/create', [PaymentController::class, 'store']);
    Route::patch('payment/update/{id}', [PaymentController::class, 'update']);
    Route::delete('payment/delete/{id}', [PaymentController::class, 'destroy']);
    Route::get('payment/status/{paymentId}', [PaymentController::class, 'checkStatus']);

    // CRITICAL REMARKS
    Route::get('remarks/fetch', [CriticalRemarkController::class, 'index']);
    Route::get('remarks/show/{id}', [CriticalRemarkController::class, 'show']);
    Route::post('remarks/create', [CriticalRemarkController::class, 'store']);
    Route::patch('remarks/update/{id}', [CriticalRemarkController::class, 'update']);
    Route::delete('remarks/delete/{id}', [CriticalRemarkController::class, 'destroy']);

    // PAYMENT METHODS
    Route::get('method/fetch', [PaymentMethodController::class, 'index']);
    Route::get('method/show/{id}', [PaymentMethodController::class, 'show']);
    Route::post('method/create', [PaymentMethodController::class, 'store']);
    Route::patch('method/update/{id}', [PaymentMethodController::class, 'update']);
    Route::delete('method/delete/{id}', [PaymentMethodController::class, 'destroy']);

    // LATE PAYMENT REASONS
    Route::get('reasons/fetch', [LatePaymentReasonController::class, 'index']);
    Route::get('reasons/show/{id}', [LatePaymentReasonController::class, 'show']);
    Route::post('reasons/create', [LatePaymentReasonController::class, 'store']);
    Route::patch('reasons/update/{id}', [LatePaymentReasonController::class, 'update']);
    Route::delete('reasons/delete/{id}', [LatePaymentReasonController::class, 'destroy']);

    // ANNOUNCEMENTS
    Route::get('announcements/fetch', [AnnouncementController::class, 'index']);
    Route::get('announcements/show/{id}', [AnnouncementController::class, 'show']);
    Route::post('announcements/create', [AnnouncementController::class, 'store']);
    Route::patch('announcements/update/{id}', [AnnouncementController::class, 'update']);
    Route::delete('announcements/delete/{id}', [AnnouncementController::class, 'destroy']);

    // COMMENTS
    Route::get('comments/fetch', [CommentController::class, 'index']);
    Route::get('comments/show/{id}', [CommentController::class, 'show']);
    Route::post('comments/create', [CommentController::class, 'store']);
    Route::patch('comments/update/{id}', [CommentController::class, 'update']);
    Route::delete('comments/delete/{id}', [CommentController::class, 'destroy']);
});