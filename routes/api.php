<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BloodDonorController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\EmergencyController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WorkerController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/photo', [MediaController::class, 'uploadProfilePhoto']);

    Route::post('/media/upload', [MediaController::class, 'upload']);
    Route::get('/media/list', [MediaController::class, 'list']);
    Route::post('/media/set-primary', [MediaController::class, 'setPrimary']);
    Route::delete('/media/{id}', [MediaController::class, 'destroy']);

    Route::get('/workers', [WorkerController::class, 'index']);
    Route::get('/workers/{id}', [WorkerController::class, 'show']);
    Route::post('/worker/apply', [WorkerController::class, 'apply']);
    Route::post('/worker/update', [WorkerController::class, 'update']);
    Route::get('/worker/categories', [WorkerController::class, 'categories']);
    Route::post('/reviews/worker', [ReviewController::class, 'rateWorker']);
    Route::get('/reviews/worker/{id}', [ReviewController::class, 'workerReviews']);

    Route::post('/book-service', [BookingController::class, 'bookService']);
    Route::get('/my-bookings', [BookingController::class, 'myBookings']);
    Route::post('/cancel-booking', [BookingController::class, 'cancelBooking']);

    Route::get('/businesses/categories', [BusinessController::class, 'categories']);
    Route::get('/business/categories', [BusinessController::class, 'categories']);
    Route::get('/businesses', [BusinessController::class, 'index']);
    Route::get('/businesses/{id}', [BusinessController::class, 'show'])->whereNumber('id');
    Route::get('/business/{id}', [BusinessController::class, 'show'])->whereNumber('id');
    Route::post('/business/add', [BusinessController::class, 'store']);
    Route::post('/reviews/business', [ReviewController::class, 'rateBusiness']);
    Route::get('/reviews/business/{id}', [ReviewController::class, 'businessReviews']);

    Route::get('/items', [MarketplaceController::class, 'index']);
    Route::post('/items/add', [MarketplaceController::class, 'store']);
    Route::post('/items/update', [MarketplaceController::class, 'update']);
    Route::delete('/items/delete', [MarketplaceController::class, 'destroy']);
    Route::get('/items/category', [MarketplaceController::class, 'categories']);

    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/send-message', [MessageController::class, 'send']);

    Route::get('/blood-donors', [BloodDonorController::class, 'index']);
    Route::post('/blood-donor/register', [BloodDonorController::class, 'register']);

    Route::get('/jobs', [JobController::class, 'index']);
    Route::post('/jobs/post', [JobController::class, 'post']);
    Route::post('/jobs/apply', [JobController::class, 'apply']);

    Route::get('/properties', [PropertyController::class, 'index']);
    Route::post('/properties/add', [PropertyController::class, 'store']);

    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{id}', [NewsController::class, 'show']);

    Route::get('/notices', [NoticeController::class, 'index']);

    Route::get('/emergency', [EmergencyController::class, 'index']);

    Route::post('/payment/bkash', [PaymentController::class, 'bkash']);
    Route::post('/payment/nagad', [PaymentController::class, 'nagad']);
    Route::post('/payment/verify', [PaymentController::class, 'verify']);

    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::post('/admin/block-user', [AdminController::class, 'blockUser']);
    Route::post('/admin/approve-worker', [AdminController::class, 'approveWorker']);
    Route::post('/admin/delete-ad', [AdminController::class, 'deleteAd']);
});
