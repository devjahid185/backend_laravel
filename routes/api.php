<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminAdminController;
use App\Http\Controllers\Api\AdminModuleController;
use App\Http\Controllers\Api\AdminReportController;
use App\Http\Controllers\Api\AdminResourceController;
use App\Http\Controllers\Api\AdminReviewController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminEmailSettingController;
use App\Http\Controllers\Api\AdminFoodDeliverySettingController;
use App\Http\Controllers\Api\AdminNotificationController;
use App\Http\Controllers\Api\AdminProfileController;
use App\Http\Controllers\Api\AdminSmsSettingController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AppVisitController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BloodDonorController;
use App\Http\Controllers\Api\BloodRequestController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\CourierController;
use App\Http\Controllers\Api\CarRentalBookingController;
use App\Http\Controllers\Api\CarRentalController;
use App\Http\Controllers\Api\DoctorAppointmentController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\ElectricityOfficeController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\FoodDeliveryController;
use App\Http\Controllers\Api\HomeBannerController;
use App\Http\Controllers\Api\HomeServiceShortcutController;
use App\Http\Controllers\Api\HospitalController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\EducationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StudentRequestController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\TeacherRequestController;
use App\Http\Controllers\Api\EmergencyController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\LaunchController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RiderController;
use App\Http\Controllers\Api\WorkerController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-google', [AuthController::class, 'loginGoogle']);
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/request-otp', [AuthController::class, 'requestOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/register-otp', [AuthController::class, 'registerWithOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPasswordWithOtp']);
Route::post('/forgot-password-email', [AuthController::class, 'requestEmailPasswordReset']);
Route::post('/verify-password-email', [AuthController::class, 'verifyEmailPasswordReset']);
Route::post('/reset-password-email', [AuthController::class, 'resetPasswordWithEmail']);
Route::get('/home-banners', [HomeBannerController::class, 'active']);
Route::get('/home-service-shortcuts', [HomeServiceShortcutController::class, 'active']);

Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
    Route::get('/admin/me', [AdminAuthController::class, 'me']);
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
    Route::get('/admin/modules', [AdminModuleController::class, 'index']);
    Route::get('/admin/stats', [AdminDashboardController::class, 'stats']);
    Route::get('/admin/profile', [AdminProfileController::class, 'show']);
    Route::post('/admin/profile', [AdminProfileController::class, 'update']);
    Route::get('/admin/notifications', [AdminNotificationController::class, 'index']);
    Route::post('/admin/notifications/send', [AdminNotificationController::class, 'send']);
    Route::get('/admin/sms-settings', [AdminSmsSettingController::class, 'show']);
    Route::put('/admin/sms-settings', [AdminSmsSettingController::class, 'update']);
    Route::post('/admin/sms-settings/test', [AdminSmsSettingController::class, 'test']);
    Route::get('/admin/email-settings', [AdminEmailSettingController::class, 'show']);
    Route::put('/admin/email-settings', [AdminEmailSettingController::class, 'update']);
    Route::post('/admin/email-settings/test', [AdminEmailSettingController::class, 'test']);
    Route::get('/admin/food-delivery-settings', [AdminFoodDeliverySettingController::class, 'show']);
    Route::put('/admin/food-delivery-settings', [AdminFoodDeliverySettingController::class, 'update']);
    Route::get('/admin/home-banners', [HomeBannerController::class, 'adminIndex']);
    Route::post('/admin/home-banners', [HomeBannerController::class, 'adminStore']);
    Route::put('/admin/home-banners/{id}', [HomeBannerController::class, 'adminUpdate'])->whereNumber('id');
    Route::delete('/admin/home-banners/{id}', [HomeBannerController::class, 'adminDestroy'])->whereNumber('id');
    Route::get('/admin/home-service-shortcuts', [HomeServiceShortcutController::class, 'adminIndex']);
    Route::post('/admin/home-service-shortcuts', [HomeServiceShortcutController::class, 'adminStore']);
    Route::put('/admin/home-service-shortcuts/{id}', [HomeServiceShortcutController::class, 'adminUpdate'])->whereNumber('id');
    Route::delete('/admin/home-service-shortcuts/{id}', [HomeServiceShortcutController::class, 'adminDestroy'])->whereNumber('id');

    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::post('/admin/users', [AdminUserController::class, 'store']);
    Route::get('/admin/users/{id}', [AdminUserController::class, 'show'])->whereNumber('id');
    Route::put('/admin/users/{id}', [AdminUserController::class, 'update'])->whereNumber('id');
    Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy'])->whereNumber('id');

    Route::get('/admin/admins', [AdminAdminController::class, 'index']);
    Route::post('/admin/admins', [AdminAdminController::class, 'store']);
    Route::put('/admin/admins/{id}', [AdminAdminController::class, 'update'])->whereNumber('id');
    Route::delete('/admin/admins/{id}', [AdminAdminController::class, 'destroy'])->whereNumber('id');

    Route::get('/admin/reports', [AdminReportController::class, 'index']);
    Route::put('/admin/reports/{id}', [AdminReportController::class, 'update'])->whereNumber('id');
    Route::delete('/admin/reports/{id}', [AdminReportController::class, 'destroy'])->whereNumber('id');

    Route::get('/admin/reviews', [AdminReviewController::class, 'index']);
    Route::delete('/admin/reviews/{id}', [AdminReviewController::class, 'destroy'])->whereNumber('id');

    Route::get('/admin/resources/{resource}', [AdminResourceController::class, 'index']);
    Route::post('/admin/resources/{resource}', [AdminResourceController::class, 'store']);
    Route::get('/admin/resources/{resource}/{id}', [AdminResourceController::class, 'show'])->whereNumber('id');
    Route::put('/admin/resources/{resource}/{id}', [AdminResourceController::class, 'update'])->whereNumber('id');
    Route::delete('/admin/resources/{resource}/{id}', [AdminResourceController::class, 'destroy'])->whereNumber('id');
});
    Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password/request-otp', [AuthController::class, 'requestPasswordChangeOtp']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/login-devices', [AuthController::class, 'loginDevices']);
    Route::delete('/login-devices/others', [AuthController::class, 'revokeOtherLoginDevices']);
    Route::delete('/login-devices/{id}', [AuthController::class, 'revokeLoginDevice'])->whereNumber('id');
    Route::post('/app-visit', [AppVisitController::class, 'store']);
    Route::post('/profile/photo', [MediaController::class, 'uploadProfilePhoto']);
    Route::post('/device-token', [DeviceTokenController::class, 'store']);
    Route::delete('/device-token', [DeviceTokenController::class, 'destroy']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->whereNumber('id');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/notifications/preferences', [NotificationPreferenceController::class, 'show']);
    Route::post('/notifications/preferences', [NotificationPreferenceController::class, 'update']);

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
    Route::post('/reviews/hospital', [ReviewController::class, 'rateHospital']);
    Route::get('/reviews/hospital/{id}', [ReviewController::class, 'hospitalReviews']);
    Route::post('/reviews/hotel', [ReviewController::class, 'rateHotel']);
    Route::get('/reviews/hotel/{id}', [ReviewController::class, 'hotelReviews']);
    Route::post('/reviews/car-rental', [ReviewController::class, 'rateCarRental']);
    Route::get('/reviews/car-rental/{id}', [ReviewController::class, 'carRentalReviews']);
    Route::post('/reviews/courier', [ReviewController::class, 'rateCourier']);
    Route::get('/reviews/courier/{id}', [ReviewController::class, 'courierReviews']);
    Route::post('/reviews/teacher', [ReviewController::class, 'rateTeacher']);
    Route::get('/reviews/teacher/{id}', [ReviewController::class, 'teacherReviews']);
    Route::post('/reviews/restaurant', [ReviewController::class, 'rateRestaurant']);
    Route::get('/reviews/restaurant/{id}', [ReviewController::class, 'restaurantReviews']);

    Route::prefix('riders')->group(function (): void {
        Route::get('/profile', [RiderController::class, 'profile']);
        Route::post('/register', [RiderController::class, 'register']);
        Route::post('/documents', [RiderController::class, 'uploadDocument']);
        Route::post('/agreement/accept', [RiderController::class, 'acceptAgreement']);
        Route::get('/dashboard', [RiderController::class, 'dashboard']);
        Route::post('/availability', [RiderController::class, 'availability']);
        Route::post('/location', [RiderController::class, 'updateLocation']);
        Route::get('/orders', [RiderController::class, 'orders']);
        Route::post('/orders/{id}/accept', [RiderController::class, 'acceptOrder'])->whereNumber('id');
        Route::post('/orders/{id}/reject', [RiderController::class, 'rejectOrder'])->whereNumber('id');
        Route::post('/orders/{id}/status', [RiderController::class, 'updateOrderStatus'])->whereNumber('id');
        Route::get('/wallet', [RiderController::class, 'wallet']);
        Route::get('/support-tickets', [RiderController::class, 'supportTickets']);
        Route::post('/support-tickets', [RiderController::class, 'createSupportTicket']);
        Route::post('/{id}/rate', [RiderController::class, 'rate'])->whereNumber('id');
    });
    Route::post('/reviews/education', [ReviewController::class, 'rateEducation']);
    Route::get('/reviews/education/{id}', [ReviewController::class, 'educationReviews']);

    Route::get('/items', [MarketplaceController::class, 'index']);
    Route::get('/items/{id}', [MarketplaceController::class, 'show'])->whereNumber('id');
    Route::post('/items/add', [MarketplaceController::class, 'store']);
    Route::post('/items/update', [MarketplaceController::class, 'update']);
    Route::delete('/items/delete', [MarketplaceController::class, 'destroy']);
    Route::get('/items/category', [MarketplaceController::class, 'categories']);
    Route::get('/items/seller/{id}', [MarketplaceController::class, 'seller'])->whereNumber('id');
    Route::post('/items/report', [MarketplaceController::class, 'report']);

    Route::get('/messages', [MessageController::class, 'index']);
    Route::get('/messages/inbox', [MessageController::class, 'inbox']);
    Route::post('/messages/upload', [MessageController::class, 'upload']);
    Route::post('/messages/typing', [MessageController::class, 'typing']);
    Route::get('/messages/typing-status', [MessageController::class, 'typingStatus']);
    Route::post('/send-message', [MessageController::class, 'send']);

    Route::get('/blood-donors', [BloodDonorController::class, 'index']);
    Route::get('/blood-donors/{id}', [BloodDonorController::class, 'show'])->whereNumber('id');
    Route::post('/blood-donor/register', [BloodDonorController::class, 'register']);
    Route::get('/blood-requests', [BloodRequestController::class, 'index']);
    Route::get('/blood-requests/{id}', [BloodRequestController::class, 'show'])->whereNumber('id');
    Route::post('/blood-requests', [BloodRequestController::class, 'store']);
    Route::post('/blood-requests/{id}/close', [BloodRequestController::class, 'close'])->whereNumber('id');

    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/categories', [JobController::class, 'categories']);
    Route::get('/jobs/{id}', [JobController::class, 'show'])->whereNumber('id');
    Route::get('/jobs/my-posts', [JobController::class, 'myPosts']);
    Route::get('/jobs/my-applications', [JobController::class, 'myApplications']);
    Route::get('/jobs/{id}/applications', [JobController::class, 'applications'])->whereNumber('id');
    Route::post('/jobs/post', [JobController::class, 'post']);
    Route::post('/jobs/{id}/update', [JobController::class, 'update'])->whereNumber('id');
    Route::post('/jobs/{id}/close', [JobController::class, 'close'])->whereNumber('id');
    Route::post('/jobs/apply', [JobController::class, 'apply']);

    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/properties/categories', [PropertyController::class, 'categories']);
    Route::get('/properties/{id}', [PropertyController::class, 'show'])->whereNumber('id');
    Route::get('/properties/my-posts', [PropertyController::class, 'myPosts']);
    Route::post('/properties/add', [PropertyController::class, 'store']);
    Route::post('/properties/{id}/update', [PropertyController::class, 'update'])->whereNumber('id');
    Route::post('/properties/{id}/close', [PropertyController::class, 'close'])->whereNumber('id');

    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{id}', [NewsController::class, 'show']);

    Route::get('/notices', [NoticeController::class, 'index']);

    Route::get('/faqs', [FaqController::class, 'index']);

    Route::get('/emergency', [EmergencyController::class, 'index']);

    Route::get('/doctors/categories', [DoctorController::class, 'categories']);
    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::get('/doctors/{id}', [DoctorController::class, 'show'])->whereNumber('id');
    Route::post('/doctors/register', [DoctorController::class, 'register']);
    Route::post('/doctors/{id}/schedules', [DoctorController::class, 'setSchedules'])->whereNumber('id');
    Route::post('/doctors/{id}/availability', [DoctorController::class, 'availability'])->whereNumber('id');

    Route::post('/doctor-appointments', [DoctorAppointmentController::class, 'book']);
    Route::get('/doctor-appointments/my', [DoctorAppointmentController::class, 'myAppointments']);
    Route::get('/doctor-appointments/doctor/{id}', [DoctorAppointmentController::class, 'doctorAppointments'])->whereNumber('id');
    Route::post('/doctor-appointments/{id}/status', [DoctorAppointmentController::class, 'updateStatus'])->whereNumber('id');

    Route::get('/teachers/categories', [TeacherController::class, 'categories']);
    Route::get('/teachers', [TeacherController::class, 'index']);
    Route::get('/teachers/{id}', [TeacherController::class, 'show'])->whereNumber('id');
    Route::post('/teachers/register', [TeacherController::class, 'register']);
    Route::post('/teachers/{id}/availability', [TeacherController::class, 'availability'])->whereNumber('id');
    Route::get('/teachers/my', [TeacherController::class, 'myTeachers']);

    Route::get('/teacher-requests', [TeacherRequestController::class, 'index']);
    Route::get('/teacher-requests/{id}', [TeacherRequestController::class, 'show'])->whereNumber('id');
    Route::post('/teacher-requests', [TeacherRequestController::class, 'store']);
    Route::post('/teacher-requests/{id}/close', [TeacherRequestController::class, 'close'])->whereNumber('id');
    Route::get('/teacher-requests/my', [TeacherRequestController::class, 'myRequests']);

    Route::get('/student-requests', [StudentRequestController::class, 'index']);
    Route::get('/student-requests/{id}', [StudentRequestController::class, 'show'])->whereNumber('id');
    Route::post('/student-requests', [StudentRequestController::class, 'store']);
    Route::post('/student-requests/{id}/close', [StudentRequestController::class, 'close'])->whereNumber('id');
    Route::get('/student-requests/my', [StudentRequestController::class, 'myRequests']);

    Route::get('/hospitals/categories', [HospitalController::class, 'categories']);
    Route::get('/hospitals', [HospitalController::class, 'index']);
    Route::get('/hospitals/{id}', [HospitalController::class, 'show'])->whereNumber('id');
    Route::post('/hospitals/register', [HospitalController::class, 'register']);
    Route::get('/hospitals/my', [HospitalController::class, 'myHospitals']);

    Route::get('/hotels/categories', [HotelController::class, 'categories']);
    Route::get('/hotels', [HotelController::class, 'index']);
    Route::get('/hotels/{id}', [HotelController::class, 'show'])->whereNumber('id');
    Route::post('/hotels/register', [HotelController::class, 'register']);
    Route::get('/hotels/my', [HotelController::class, 'myHotels']);

    Route::get('/restaurants/categories', [RestaurantController::class, 'categories']);
    Route::get('/restaurants', [RestaurantController::class, 'index']);
    Route::get('/restaurants/{id}', [RestaurantController::class, 'show'])->whereNumber('id');
    Route::post('/restaurants/register', [RestaurantController::class, 'register']);
    Route::get('/restaurants/my', [RestaurantController::class, 'myRestaurants']);

    Route::prefix('food')->group(function (): void {
        Route::get('/home', [FoodDeliveryController::class, 'home']);
        Route::get('/restaurants', [FoodDeliveryController::class, 'restaurants']);
        Route::get('/restaurants/{id}', [FoodDeliveryController::class, 'restaurant'])->whereNumber('id');
        Route::get('/items', [FoodDeliveryController::class, 'items']);
        Route::get('/items/{id}', [FoodDeliveryController::class, 'item'])->whereNumber('id');
        Route::get('/owner/dashboard', [FoodDeliveryController::class, 'ownerDashboard']);
        Route::get('/owner/restaurants', [FoodDeliveryController::class, 'ownerRestaurants']);
        Route::post('/owner/restaurants', [FoodDeliveryController::class, 'saveOwnerRestaurant']);
        Route::get('/owner/items', [FoodDeliveryController::class, 'ownerItems']);
        Route::post('/owner/items', [FoodDeliveryController::class, 'saveOwnerItem']);
        Route::delete('/owner/items/{id}', [FoodDeliveryController::class, 'deleteOwnerItem'])->whereNumber('id');
        Route::get('/owner/orders', [FoodDeliveryController::class, 'ownerOrders']);
        Route::get('/owner/reviews', [FoodDeliveryController::class, 'ownerReviews']);
        Route::post('/owner/reviews/{id}/reply', [FoodDeliveryController::class, 'ownerReplyReview'])->whereNumber('id');
        Route::get('/addresses', [FoodDeliveryController::class, 'addresses']);
        Route::post('/addresses', [FoodDeliveryController::class, 'saveAddress']);
        Route::delete('/addresses/{id}', [FoodDeliveryController::class, 'deleteAddress'])->whereNumber('id');
        Route::get('/cart-count', [FoodDeliveryController::class, 'cartCount']);
        Route::get('/cart', [FoodDeliveryController::class, 'cart']);
        Route::post('/cart/items', [FoodDeliveryController::class, 'addToCart']);
        Route::post('/cart/items/{id}', [FoodDeliveryController::class, 'updateCartItem'])->whereNumber('id');
        Route::delete('/cart/items/{id}', [FoodDeliveryController::class, 'removeCartItem'])->whereNumber('id');
        Route::delete('/cart', [FoodDeliveryController::class, 'clearCart']);
        Route::post('/checkout', [FoodDeliveryController::class, 'checkout']);
        Route::get('/orders', [FoodDeliveryController::class, 'orders']);
        Route::get('/orders/{id}', [FoodDeliveryController::class, 'order'])->whereNumber('id');
        Route::post('/orders/{id}/cancel', [FoodDeliveryController::class, 'cancelOrder'])->whereNumber('id');
        Route::post('/orders/{id}/status', [FoodDeliveryController::class, 'updateOrderStatus'])->whereNumber('id');
        Route::get('/favorites', [FoodDeliveryController::class, 'favorites']);
        Route::post('/favorites/toggle', [FoodDeliveryController::class, 'toggleFavorite']);
        Route::get('/reviews', [FoodDeliveryController::class, 'reviews']);
        Route::post('/reviews', [FoodDeliveryController::class, 'review']);
    });

    Route::get('/education/categories', [EducationController::class, 'categories']);
    Route::get('/education', [EducationController::class, 'index']);
    Route::get('/education/{id}', [EducationController::class, 'show'])->whereNumber('id');
    Route::post('/education/register', [EducationController::class, 'register']);
    Route::get('/education/my', [EducationController::class, 'myInstitutes']);

    Route::get('/couriers/companies', [CourierController::class, 'companies']);
    Route::get('/couriers/offices', [CourierController::class, 'offices']);
    Route::get('/couriers/company/{id}', [CourierController::class, 'companyShow'])->whereNumber('id');
    Route::get('/couriers/offices/{id}', [CourierController::class, 'officeShow'])->whereNumber('id');
    Route::post('/couriers/register', [CourierController::class, 'register']);
    Route::get('/couriers/my', [CourierController::class, 'myOffices']);

    Route::get('/car-rentals/categories', [CarRentalController::class, 'categories']);
    Route::get('/car-rentals', [CarRentalController::class, 'index']);
    Route::get('/car-rentals/{id}', [CarRentalController::class, 'show'])->whereNumber('id');
    Route::post('/car-rentals', [CarRentalController::class, 'store']);
    Route::get('/car-rentals/my', [CarRentalController::class, 'myRentals']);

    Route::get('/launches', [LaunchController::class, 'index']);
    Route::get('/launches/{id}', [LaunchController::class, 'show'])->whereNumber('id');
    Route::post('/launches/register', [LaunchController::class, 'store']);
    Route::get('/launches/my', [LaunchController::class, 'myLaunches']);

    Route::post('/car-rental-bookings', [CarRentalBookingController::class, 'book']);
    Route::get('/car-rental-bookings/my', [CarRentalBookingController::class, 'myBookings']);
    Route::get('/car-rental-bookings/owner/{id}', [CarRentalBookingController::class, 'ownerBookings'])->whereNumber('id');
    Route::post('/car-rental-bookings/{id}/status', [CarRentalBookingController::class, 'updateStatus'])->whereNumber('id');

    Route::get('/electricity/offices', [ElectricityOfficeController::class, 'index']);
    Route::get('/electricity/offices/{id}', [ElectricityOfficeController::class, 'show'])->whereNumber('id');
    Route::post('/electricity/register', [ElectricityOfficeController::class, 'register']);
    Route::get('/electricity/my', [ElectricityOfficeController::class, 'myOffices']);

    Route::post('/payment/bkash', [PaymentController::class, 'bkash']);
    Route::post('/payment/nagad', [PaymentController::class, 'nagad']);
    Route::post('/payment/verify', [PaymentController::class, 'verify']);

});
