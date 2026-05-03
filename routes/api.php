<?php

use App\Http\Controllers\Api\Building\RoomController;
use App\Http\Controllers\Api\Building\RuleController;
use App\Http\Controllers\Api\User\AnnouncementController;
use App\Http\Controllers\Api\User\AuthController as UserAuthController;
use App\Http\Controllers\Api\User\CommentController;
use App\Http\Controllers\Api\User\CriticalRemarkController;
use App\Http\Controllers\Api\User\LatePaymentReasonController;
use App\Http\Controllers\Api\User\PaymentController;
use App\Http\Controllers\Api\User\PaymentMethodController;
use App\Http\Controllers\Api\User\UserController;
use Illuminate\Support\Facades\Route;

// Routes that do NOT require authentication
Route::middleware([
    // \Illuminate\Session\Middleware\StartSession::class, 
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class
])->group(function () {
    Route::post('user/create', [UserController::class, "store"])->name("user.create");
    Route::post('/payment/callback', [PaymentController::class, 'callback']);
    Route::post('user/auth', [UserAuthController::class, 'login'])->name('user.auth');
    Route::post('user/forgot-password', [UserAuthController::class, 'forgotPassword'])->name('user.forgotPassword');
});

// Routes that require authentication
Route::middleware([
    \Illuminate\Session\Middleware\StartSession::class, // start session
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'auth:sanctum'
])->group(function () {

    // User routes
    Route::get("user/fetch", [UserController::class, "index"])->name("user.index");
    Route::get("user/show/{id}", [UserController::class, "show"])->name('user.show');
    Route::patch("user/update/{id}", [UserController::class, "update"])->name("user.update");
    Route::patch('user/update/phone/{id}', [UserController::class, 'updatePhoneNumber'])->name('user.updatePhone');
    Route::delete("user/delete/{id}", [UserController::class, "destroy"])->name('user.destroy');
    Route::post('user/logout', [UserAuthController::class, 'logout'])->name('user.logout');

    // Rules routes
    Route::get('rule/fetch', [RuleController::class, 'index'])->name('rule.index');
    Route::get('rule/show/{id}', [RuleController::class, 'show'])->name('rule.show');
    Route::post('rule/create', [RuleController::class, 'store'])->name('rule.create');
    Route::patch('rule/update/{id}', [RuleController::class, 'update'])->name('rule.update');
    Route::delete('rule/delete/{id}', [RuleController::class, 'destroy'])->name('rule.destroy');

    // Rooms routes
    Route::get('room/fetch', [RoomController::class, 'index'])->name('room.index');
    Route::get('room/show/{id}', [RoomController::class, 'show'])->name('room.show');
    Route::post('room/create', [RoomController::class, 'store'])->name('room.create');
    Route::patch('room/update/{id}', [RoomController::class, 'update'])->name('room.update');
    Route::patch('room/update/status/{id}', [RoomController::class, 'updateRoomStatus'])->name('room.updateRoomStatus');
    Route::delete('room/delete/{id}', [RoomController::class, 'destroy'])->name('room.destroy');

    // Payments routes
    Route::get('payment/fetch', [PaymentController::class, 'index'])->name('payment.index');
    Route::get('payment/show/{id}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('payment/create', [PaymentController::class, 'store'])->name('payment.create');
    Route::patch('payment/update/{id}', [PaymentController::class, 'update'])->name('payment.update');
    Route::delete('payment/delete/{id}', [PaymentController::class, 'destroy'])->name('payment.destroy');

    // Critical Remarks routes
    Route::get('remarks/fetch', [CriticalRemarkController::class, 'index'])->name('remarks.index');
    Route::post('remarks/create', [CriticalRemarkController::class, 'store'])->name('remarks.create');
    Route::patch('remarks/update/{id}', [CriticalRemarkController::class, 'update'])->name('remarks.update');
    Route::delete('remarks/delete/{id}', [CriticalRemarkController::class, 'destroy'])->name('remarks.destroy');

    // Payment Methods routes
    Route::get('method/fetch', [PaymentMethodController::class, 'index'])->name('method.index');
    Route::get('method/show/{id}', [PaymentMethodController::class, 'show'])->name('method.show');
    Route::post('method/create', [PaymentMethodController::class, 'store'])->name('method.create');
    Route::patch('method/update/{id}', [PaymentMethodController::class, 'update'])->name('method.update');
    Route::delete('method/delete/{id}', [PaymentMethodController::class, 'destroy'])->name('method.destroy');

    // Late Payment Reasons
    Route::get('reasons/fetch', [LatePaymentReasonController::class, 'index'])->name('reasons.index');
    Route::get('reasons/show/{id}', [LatePaymentReasonController::class, 'show'])->name('reasons.show');
    Route::post('reasons/create', [LatePaymentReasonController::class, 'store'])->name('reasons.create');
    Route::patch('reasons/update/{id}', [LatePaymentReasonController::class, 'update'])->name('reasons.update');
    Route::delete('reasons/delete/{id}', [LatePaymentReasonController::class, 'destroy'])->name('reasons.destroy');

    // Announcements Routes
    Route::get('announcements/fetch', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('announcements/show/{id}', [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::post('announcements/create', [AnnouncementController::class, 'store'])->name('announcements.create');
    Route::patch('announcements/update/{id}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('announcements/delete/{id}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');


    // Comments Routes
    Route::get('comments/fetch', [CommentController::class, 'index'])->name('comments.index');
    Route::get('comments/show/{id}', [CommentController::class, 'show'])->name('comments.show');
    Route::post('comments/create', [CommentController::class, 'store'])->name('comments.create');
    Route::patch('comments/update/{id}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('comments/delete/{id}', [CommentController::class, 'destroy'])->name('comments.destroy');
});
