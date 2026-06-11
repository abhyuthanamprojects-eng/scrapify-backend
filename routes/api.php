<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HomeApplianceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('register/send-otp', [AuthController::class, 'sendRegistrationOtp']);
    Route::post('register/verify-otp', [AuthController::class, 'verifyRegistrationOtp']);
    Route::post('login/send-otp', [AuthController::class, 'sendLoginOtp']);
    Route::post('login/verify-otp', [AuthController::class, 'verifyLoginOtp']);
    Route::post('resend-otp', [AuthController::class, 'resendOtp']);
    Route::get('user-types', [\App\Http\Controllers\Api\AuthMetaController::class, 'userTypes']);
});

Route::prefix('channel-partner/registration')->group(function () {
    Route::post('request', [\App\Http\Controllers\Api\ChannelPartnerOnboardingController::class, 'registrationRequest']);
    Route::get('request-status', [\App\Http\Controllers\Api\ChannelPartnerOnboardingController::class, 'registrationStatus']);
});

// Public Pages & Contact
Route::get('pages/{slug}', [\App\Http\Controllers\Api\PageController::class, 'show']);
Route::post('contact', [\App\Http\Controllers\Api\PageController::class, 'submitContact']);
Route::get('warehouses', [\App\Http\Controllers\Api\WarehouseController::class, 'index']);

// Referral - Public validation (pre-signup)
Route::post('referral/validate-code', [\App\Http\Controllers\Api\ReferralController::class, 'validateCode']);

// Locations & Slots
Route::get('serviceable-cities', [\App\Http\Controllers\Api\LocationController::class, 'serviceableCities']);
Route::get('pickup-slots', [\App\Http\Controllers\Api\LocationController::class, 'pickupSlots']);

// App Settings & Service Coverage
// App Settings & Service Coverage
Route::match(['get', 'post'], 'app-settings', [\App\Http\Controllers\Api\AppSettingsController::class, 'index']);
Route::prefix('v1')->group(function () {
    Route::get('service-coverage', [\App\Http\Controllers\Api\ServiceCoverageController::class, 'index']);
    Route::post('waitlist', [\App\Http\Controllers\Api\WaitlistController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {
    // App Settings (Private)
    Route::post('app-settings/language', [\App\Http\Controllers\Api\AppSettingsController::class, 'updateLanguage']);

    // Help & Support (all authenticated roles)
    Route::post('help-support', [\App\Http\Controllers\Api\HelpSupportController::class, 'store']);
    Route::get('help-support', [\App\Http\Controllers\Api\HelpSupportController::class, 'index']);

    // Referral (customer)
    Route::middleware('role:customer')->group(function () {
        Route::get('referral/my-code', [\App\Http\Controllers\Api\ReferralController::class, 'myCode']);
        Route::get('referral/my-rewards', [\App\Http\Controllers\Api\ReferralController::class, 'myRewards']);
        Route::post('referral/validate-coupon', [\App\Http\Controllers\Api\ReferralController::class, 'validateCoupon']);
    });
    Route::prefix('auth')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);

        // Profile Enhancements
        Route::post('profile/update', [\App\Http\Controllers\Api\ProfileController::class, 'updateProfile']);
        Route::post('profile/bank-details', [\App\Http\Controllers\Api\ProfileController::class, 'updateBankDetails']);
        Route::post('profile/kyc', [\App\Http\Controllers\Api\ProfileController::class, 'uploadKyc']);
        Route::delete('profile', [\App\Http\Controllers\Api\ProfileController::class, 'deleteAccount']);

        // Addresses and Payment Details CRUD
        Route::apiResource('profile/addresses', \App\Http\Controllers\Api\AddressController::class)->except(['show']);
        Route::apiResource('profile/payment-details', \App\Http\Controllers\Api\PaymentDetailController::class)->except(['show']);
    });



    // ============================================
    // Location APIs (States & Cities)
    // ============================================
    Route::get('states', [\App\Http\Controllers\Api\LocationController::class, 'states']);
    Route::get('cities', [\App\Http\Controllers\Api\LocationController::class, 'cities']);

    // ============================================
    // Screen: Home (Categories & Recent Orders)
    // ============================================
    Route::middleware(['role:customer|channel_partner|pickup_boy'])->group(function () {
        Route::get('categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
        Route::get('subcategories', [\App\Http\Controllers\Api\CategoryController::class, 'subcategories']);
        Route::get('categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'show']);

        // Home Appliances Specialized Details
        Route::get('home-appliances/details', [HomeApplianceController::class, 'details']);
        Route::post('home-appliances/estimate', [HomeApplianceController::class, 'estimate']);

        // Recent Orders (reuse list endpoint)
        // Frontend can call: /api/pickup-requests?limit=5
    });

    // ============================================
    // Screen: My Pickups (History & Stats)
    // ============================================
    Route::middleware(['role:customer|channel_partner'])->group(function () {
        // Donation Requests
        Route::get('donation-products', [\App\Http\Controllers\Api\DonationRequestController::class, 'products']);
        Route::post('donation-request', [\App\Http\Controllers\Api\DonationRequestController::class, 'store']);
        Route::get('donation-requests', [\App\Http\Controllers\Api\DonationRequestController::class, 'index']);
        Route::get('donation-requests/{id}', [\App\Http\Controllers\Api\PickupRequestController::class, 'show']); // Reuse show logic
        Route::get('donation-requests/{id}/tracking', [\App\Http\Controllers\Api\PickupRequestController::class, 'tracking']); // Reuse tracking
        Route::post('pickup-requests/{id}/clone-as-donation', [\App\Http\Controllers\Api\DonationRequestController::class, 'cloneAsDonation']);

        // Corporate Bookings (Migrated to generic/refactored routes)

        Route::get('pickup-requests', [\App\Http\Controllers\Api\PickupRequestController::class, 'index']); // List with filters
        Route::get('pickup-requests/stats', [\App\Http\Controllers\Api\PickupRequestController::class, 'stats']); // New Stats Endpoint
        Route::get('pickup-requests/{id}', [\App\Http\Controllers\Api\PickupRequestController::class, 'show']);
        Route::get('pickup-requests/{id}/tracking', [\App\Http\Controllers\Api\PickupRequestController::class, 'tracking']);

        // Raising a Request
        Route::post('pickup-request', [\App\Http\Controllers\Api\PickupRequestController::class, 'store']);
        Route::post('pickup-requests', [\App\Http\Controllers\Api\PickupRequestController::class, 'store']);
        Route::post('pickup-requests/check-booking-eligibility', [\App\Http\Controllers\Api\PickupRequestController::class, 'checkBookingEligibility']);

        // Reschedule Pickup
        Route::get('pickup-requests/{id}/reschedule-slots', [\App\Http\Controllers\Api\PickupBoyController::class, 'getRescheduleSlots']);
        Route::post('pickup-requests/{id}/reschedule', [\App\Http\Controllers\Api\PickupRequestController::class, 'reschedule']);

        // Cancel Pickup
        Route::post('pickup-requests/{id}/cancel', [\App\Http\Controllers\Api\PickupRequestController::class, 'cancel']);
        Route::post('pickup-requests/{id}/review', [\App\Http\Controllers\Api\PickupRequestController::class, 'submitReview']);

        // Image Upload
        Route::post('pickup-images/upload', [\App\Http\Controllers\Api\ImageController::class, 'uploadPickupImages']);
        Route::get('pickup-requests/{id}/images', [\App\Http\Controllers\Api\ImageController::class, 'getPickupImages']);
        Route::delete('pickup-images/{id}', [\App\Http\Controllers\Api\ImageController::class, 'deleteImage']);
    });

    // ============================================
    // Notifications
    // ============================================
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::get('/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
        Route::post('/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
        Route::post('/fcm-token', [\App\Http\Controllers\Api\NotificationController::class, 'updateFcmToken']);
    });

    // ============================================
    // Pickup Boy Flow
    // ============================================
    Route::prefix('pickup-boy')->middleware('role:pickup_boy')->group(function () {
        Route::get('profile', [\App\Http\Controllers\Api\PickupBoyController::class, 'getProfile']);
        Route::get('profile/status', [\App\Http\Controllers\Api\PickupBoyController::class, 'getProfileStatus']);
        Route::get('dashboard', [\App\Http\Controllers\Api\PickupBoyController::class, 'dashboard']);
        Route::get('assignments', [\App\Http\Controllers\Api\PickupBoyController::class, 'getAssignments']);
        Route::get('pickups', [\App\Http\Controllers\Api\PickupBoyController::class, 'getAssignments']);
        Route::get('pickups/{id}', [\App\Http\Controllers\Api\PickupBoyController::class, 'show']);
        Route::post('pickups/{id}/accept', [\App\Http\Controllers\Api\PickupBoyController::class, 'acceptAssignment']);
        Route::post('pickups/{id}/reject', [\App\Http\Controllers\Api\PickupBoyController::class, 'rejectAssignment']);
        Route::post('pickups/{id}/status', [\App\Http\Controllers\Api\PickupBoyController::class, 'updateTravelStatus']);
        Route::post('pickups/{id}/start', [\App\Http\Controllers\Api\PickupBoyController::class, 'startPickup']);
        Route::post('pickups/{id}/arrived', [\App\Http\Controllers\Api\PickupBoyController::class, 'arrivePickup']);
        Route::post('pickups/{id}/verify', [\App\Http\Controllers\Api\PickupBoyController::class, 'verifyPickup']);
        Route::post('pickups/{id}/complete', [\App\Http\Controllers\Api\PickupBoyController::class, 'verifyPickup']);
        Route::post('pickups/{id}/update-status', [\App\Http\Controllers\Api\PickupBoyController::class, 'updateAssignmentStatus']);
        Route::post('pickups/{id}/update-final-price', [\App\Http\Controllers\Api\PickupBoyController::class, 'updateFinalPrice']);
        Route::post('pickups/{id}/update-amount', [\App\Http\Controllers\Api\PickupBoyController::class, 'updateFinalAmount']);
        Route::post('pickups/{id}/add-item', [\App\Http\Controllers\Api\PickupBoyController::class, 'addPickupItem']);
        Route::post('pickups/{id}/reschedule-request', [\App\Http\Controllers\Api\PickupBoyController::class, 'requestReschedule']);
        Route::post('pickups/{id}/reschedule', [\App\Http\Controllers\Api\PickupBoyController::class, 'reschedulePickup']);
        Route::post('pickups/{id}/cancel', [\App\Http\Controllers\Api\PickupBoyController::class, 'cancelPickup']);
        Route::post('location', [\App\Http\Controllers\Api\PickupBoyController::class, 'updateLocation']);
        Route::post('location/update', [\App\Http\Controllers\Api\PickupBoyController::class, 'updateLocation']);
        Route::post('status', [\App\Http\Controllers\Api\PickupBoyController::class, 'toggleStatus']);
        Route::get('pickups/{id}/reschedule-slots', [\App\Http\Controllers\Api\PickupBoyController::class, 'getRescheduleSlots']);
        Route::post('pickups/{id}/complete', [\App\Http\Controllers\Api\PickupBoyController::class, 'verifyPickup']); // Alias
    });

    // Warehouse Operations
    // ============================================
    // Warehouse App APIs (admin/warehouse/channel_partner)
    Route::prefix('warehouse')->middleware('role:admin|warehouse|channel_partner')->group(function () {
        Route::get('app/profile', [\App\Http\Controllers\Api\Warehouse\WarehouseAppController::class, 'profile']);
        Route::get('app/orders', [\App\Http\Controllers\Api\Warehouse\WarehouseAppController::class, 'orders']);
        Route::get('app/available-pickup-boys', [\App\Http\Controllers\Api\Warehouse\WarehouseAppController::class, 'availablePickupBoys']);
        Route::post('app/assign-pickup-boy', [\App\Http\Controllers\Api\Warehouse\AssignmentController::class, 'assign']);
        Route::post('app/pickups/{pickupId}/reassign', [\App\Http\Controllers\Api\Warehouse\AssignmentController::class, 'reassign']);
    });

    Route::prefix('warehouse')->middleware('role:admin|warehouse')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\Api\WarehouseController::class, 'dashboard']);
        Route::get('profile', [\App\Http\Controllers\Api\WarehouseController::class, 'profile']);

        Route::get('requests', [\App\Http\Controllers\Api\WarehouseController::class, 'requests']);
        Route::get('requests/{id}/assignable-pickup-boys', [\App\Http\Controllers\Api\WarehouseController::class, 'assignablePickupBoys']);
        Route::get('requests/{id}', [\App\Http\Controllers\Api\WarehouseController::class, 'showRequest']);
        Route::post('requests/{id}/assign', [\App\Http\Controllers\Api\WarehouseController::class, 'assignPickup']);
        Route::post('requests/{id}/reassign', [\App\Http\Controllers\Api\WarehouseController::class, 'reassignPickup']);

        Route::get('pickup-boys', [\App\Http\Controllers\Api\WarehouseController::class, 'pickupBoys']);
        Route::get('pickup-boys/{id}', [\App\Http\Controllers\Api\WarehouseController::class, 'showPickupBoy']);

        Route::get('shipments', [\App\Http\Controllers\Api\WarehouseController::class, 'shipments']);
        Route::get('{id}', [\App\Http\Controllers\Api\WarehouseController::class, 'show']);
        Route::post('{id}/inventory', [\App\Http\Controllers\Api\WarehouseController::class, 'updateInventory']);
        Route::get('{id}/summary', [\App\Http\Controllers\Api\WarehouseController::class, 'inventorySummary']);
        Route::get('{id}/logs', [\App\Http\Controllers\Api\WarehouseController::class, 'inventoryLogs']);
    });

    // ============================================
    // REQUEST LIFECYCLE REFACTORING - NEW ROUTES
    // ============================================

    // Request CRUD (all authenticated users)
    Route::prefix('requests')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\RequestController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\RequestController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\RequestController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\RequestController::class, 'update']);
        Route::post('/{id}/cancel', [\App\Http\Controllers\Api\RequestController::class, 'cancel']);
        Route::get('/{id}/status-history', [\App\Http\Controllers\Api\RequestController::class, 'statusHistory']);
        Route::get('/{id}/next-actions', [\App\Http\Controllers\Api\RequestController::class, 'getNextActions']);
    });

    // Warehouse Operations (Warehouse Role)
    Route::prefix('warehouse/requests')->middleware('role:warehouse')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Warehouse\WarehouseRequestController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\Warehouse\WarehouseRequestController::class, 'show']);
        Route::post('/{id}/assign-pickup-boy', [\App\Http\Controllers\Api\Warehouse\WarehouseRequestController::class, 'assignPickupBoy']);
        Route::put('/{id}/reassign-pickup-boy', [\App\Http\Controllers\Api\Warehouse\WarehouseRequestController::class, 'reassignPickupBoy']);
        Route::post('/{id}/confirm-received', [\App\Http\Controllers\Api\Warehouse\WarehouseRequestController::class, 'confirmReceived']);
        Route::post('/{id}/move-to-payment-pending', [\App\Http\Controllers\Api\Warehouse\WarehouseRequestController::class, 'moveToPaymentPending']);
        Route::post('/{id}/mark-as-received', [\App\Http\Controllers\Api\Warehouse\WarehouseRequestController::class, 'markAsReceived']);
        Route::post('/{id}/initiate-payment', [\App\Http\Controllers\Api\Warehouse\WarehouseRequestController::class, 'initiatePayment']);
        Route::post('/{id}/confirm-payment', [\App\Http\Controllers\Api\Warehouse\WarehouseRequestController::class, 'confirmPayment']);
    });

    Route::get('warehouse/dashboard', [\App\Http\Controllers\Api\Warehouse\WarehouseRequestController::class, 'dashboard'])->middleware('role:warehouse');

    // Corporate Booking - Estimate Management
    Route::prefix('corporate-bookings')->group(function () {
        Route::get('/options', [\App\Http\Controllers\Api\CorporateBookingController::class, 'options']);
        Route::get('/', [\App\Http\Controllers\Api\CorporateBookingController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\CorporateBookingController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\CorporateBookingController::class, 'show']);
    });

    Route::prefix('corporate-bookings')->middleware('role:warehouse|admin')->group(function () {
        Route::post('/{id}/estimate', [\App\Http\Controllers\Api\CorporateBookingController::class, 'createEstimate']);
        Route::put('/{id}/estimate', [\App\Http\Controllers\Api\CorporateBookingController::class, 'updateEstimate']);
        Route::post('/{id}/estimate/share', [\App\Http\Controllers\Api\CorporateBookingController::class, 'shareEstimate']);
    });

    Route::prefix('corporate-bookings')->middleware('role:customer|admin')->group(function () {
        Route::get('/{id}/estimate', [\App\Http\Controllers\Api\CorporateBookingController::class, 'getEstimate']);
        Route::post('/{id}/estimate/approve', [\App\Http\Controllers\Api\CorporateBookingController::class, 'approveEstimate']);
        Route::post('/{id}/estimate/reject', [\App\Http\Controllers\Api\CorporateBookingController::class, 'rejectEstimate']);
    });

    // Payment Processing (Admin & Payment Admin Roles)
    Route::prefix('requests')->middleware('role:admin|payment_admin')->group(function () {
        Route::post('/{id}/payment/pending', [\App\Http\Controllers\Api\PaymentController::class, 'moveToPaymentPending']);
        Route::post('/{id}/payment/process', [\App\Http\Controllers\Api\PaymentController::class, 'processPayment']);
        Route::post('/{id}/payment/confirm', [\App\Http\Controllers\Api\PaymentController::class, 'confirmPayment']);
        Route::get('/{id}/payment', [\App\Http\Controllers\Api\PaymentController::class, 'getPaymentDetails']);
    });

    // Donation Requests
    Route::prefix('donations')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\DonationController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\DonationController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\DonationController::class, 'show']);
        Route::post('/{id}/cancel', [\App\Http\Controllers\Api\DonationController::class, 'cancel']);
    });

    Route::get('donations/statistics', [\App\Http\Controllers\Api\DonationController::class, 'statistics'])->middleware('role:warehouse|admin');

    // Channel Partner Flow
    Route::prefix('channel-partner')->middleware('role:channel_partner')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'dashboard']);
        Route::get('profile', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'profile']);
        Route::put('profile', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'updateProfile']);

        Route::apiResource('customers', \App\Http\Controllers\Api\ChannelPartnerCustomerController::class);
        Route::post('pickup-requests', [\App\Http\Controllers\Api\ChannelPartnerPickupController::class, 'store']);
        Route::get('pickup-requests/{id}/tracking', [\App\Http\Controllers\Api\ChannelPartnerPickupController::class, 'tracking']);
        Route::get('pickup-requests/{id}/available-pickup-boys', [\App\Http\Controllers\Api\ChannelPartnerPickupController::class, 'availablePickupBoys']);
        Route::post('pickup-requests/{id}/assign', [\App\Http\Controllers\Api\ChannelPartnerPickupController::class, 'assign']);
        Route::post('pickup-requests/{id}/reassign', [\App\Http\Controllers\Api\ChannelPartnerPickupController::class, 'assign']);
        Route::post('pickup-requests/{id}/deliver-to-warehouse', [\App\Http\Controllers\Api\ChannelPartnerPickupController::class, 'deliverToWarehouse']);
        Route::get('settlements', [\App\Http\Controllers\Api\ChannelPartnerPickupController::class, 'settlements']);

        Route::get('orders', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'orders']);
        Route::get('orders/{id}', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'orderDetail']);

        Route::get('pickup-boys', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'pickupBoys']);
        Route::get('pickup-boys/{id}', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'showPickupBoy']);
        Route::post('pickup-boys', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'storePickupBoyRequest']);
        Route::put('pickup-boys/{id}', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'updatePickupBoyRequest']);
        Route::post('pickup-boys/{id}/status-request', fn($id) => app(\App\Http\Controllers\Api\ChannelPartnerController::class)->submitStatusRequest(request(), 'pickup_boy', $id));

        Route::get('warehouses', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'warehouses']);
        Route::get('warehouses/{id}', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'showWarehouse']);
        Route::post('warehouses', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'storeWarehouseRequest']);
        Route::put('warehouses/{id}', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'updateWarehouseRequest']);
        Route::post('warehouses/{id}/status-request', fn($id) => app(\App\Http\Controllers\Api\ChannelPartnerController::class)->submitStatusRequest(request(), 'warehouse', $id));

        Route::get('approval-requests', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'approvalRequests']);
        Route::get('approval-requests/{id}', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'showApprovalRequest']);
        Route::post('status-request', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'handleStatusRequest']);


        // Onboarding
        Route::prefix('onboarding')->group(function () {
            Route::post('pickup-boy', [\App\Http\Controllers\Api\ChannelPartnerOnboardingController::class, 'onboardPickupBoy']);
            Route::post('warehouse', [\App\Http\Controllers\Api\ChannelPartnerOnboardingController::class, 'onboardWarehouse']);
            Route::get('requests', [\App\Http\Controllers\Api\ChannelPartnerOnboardingController::class, 'onboardingRequests']);
        });

        // Finance & Payouts
        Route::get('payouts', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'payouts']);
        Route::get('withdrawals', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'listWithdrawals']);
        Route::post('withdrawals', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'storeWithdrawal']);
    });


    // ============================================
    // Referral Management (Admin + Channel Partner)
    // ============================================
    Route::prefix('admin')->middleware('role:admin|channel_partner')->group(function () {
        Route::get('referral-settings', [\App\Http\Controllers\Api\Admin\ReferralSettingController::class, 'index']);
        Route::post('referral-settings', [\App\Http\Controllers\Api\Admin\ReferralSettingController::class, 'store']);
        Route::put('referral-settings/{id}', [\App\Http\Controllers\Api\Admin\ReferralSettingController::class, 'update']);

        Route::get('referrals', [\App\Http\Controllers\Api\Admin\ReferralAdminController::class, 'referrals']);
        Route::get('referral-coupons', [\App\Http\Controllers\Api\Admin\ReferralAdminController::class, 'coupons']);
        Route::put('referral-coupons/{id}/cancel', [\App\Http\Controllers\Api\Admin\ReferralAdminController::class, 'cancelCoupon']);
    });

    // ============================================
    // Admin Flow
    // ============================================
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        // Location lookup
        Route::post('location/reverse-geocode', [\App\Http\Controllers\Admin\WarehouseController::class, 'reverseGeocode']);

        // Warehouse pickup-boy mapping
        Route::get('warehouses/{warehouse}/pickup-boys', [\App\Http\Controllers\Admin\WarehouseController::class, 'pickupBoys']);
        Route::post('warehouses/{warehouse}/assign-pickup-boy', [\App\Http\Controllers\Admin\WarehouseController::class, 'attachPickupBoy']);
        Route::delete('warehouses/{warehouse}/pickup-boys/{userId}', [\App\Http\Controllers\Admin\WarehouseController::class, 'detachPickupBoy']);

        // Pickup price (admin override + logs)
        Route::put('pickups/{id}/final-price', [\App\Http\Controllers\Api\Admin\PickupPriceController::class, 'update']);
        Route::get('pickups/{id}/price-logs', [\App\Http\Controllers\Api\Admin\PickupPriceController::class, 'logs']);

        // User Types
        Route::patch('user-types/{code}/visibility', [\App\Http\Controllers\Api\Admin\AdminUserTypeController::class, 'updateVisibility']);
        // Corporate Bookings
        Route::get('corporate-bookings', [\App\Http\Controllers\Api\Admin\CorporateBookingController::class, 'index']);
        Route::post('corporate-bookings/{id}/quote', [\App\Http\Controllers\Api\Admin\CorporateBookingController::class, 'quote']);
        Route::post('corporate-bookings/{id}/assign', [\App\Http\Controllers\Api\Admin\AdminController::class, 'assignPickup']);
        Route::post('corporate-bookings/{id}/reassign', [\App\Http\Controllers\Api\Admin\AdminController::class, 'reassignPickup']);

        Route::get('logs', [\App\Http\Controllers\Api\Admin\AdminController::class, 'logs']);
        Route::get('pickups', [\App\Http\Controllers\Api\Admin\AdminController::class, 'listPickups']);
        Route::get('pickups/{id}', [\App\Http\Controllers\Api\Admin\AdminController::class, 'getPickup']);
        Route::post('pickups/{id}/status', [\App\Http\Controllers\Api\Admin\AdminController::class, 'updatePickupStatus']);
        Route::get('pickups/{id}/tracking', [\App\Http\Controllers\Api\Admin\AdminController::class, 'getPickupTracking']);
        Route::get('pickups/{id}/reschedule-requests', [\App\Http\Controllers\Api\Admin\AdminController::class, 'getRescheduleRequests']);
        Route::post('pickups/{id}/approve-reschedule', [\App\Http\Controllers\Api\Admin\AdminController::class, 'approveReschedule']);
        Route::post('pickups/{id}/reject-reschedule', [\App\Http\Controllers\Api\Admin\AdminController::class, 'rejectReschedule']);

        // Pickup Boy Management
        Route::get('pickup-boys', [\App\Http\Controllers\Api\Admin\AdminController::class, 'listPickupBoys']);
        Route::get('pickup-boys/{id}', [\App\Http\Controllers\Api\Admin\AdminController::class, 'getPickupBoy']);
        Route::post('pickup-boys/{id}/status', [\App\Http\Controllers\Api\Admin\AdminController::class, 'togglePickupBoyStatus']);
        Route::get('pickup-boys/{id}/pickups', [\App\Http\Controllers\Api\Admin\AdminController::class, 'getPickupBoyPickups']);
        Route::get('pickup-boys/{id}/tracking', [\App\Http\Controllers\Api\Admin\AdminController::class, 'getPickupBoyTracking']);

        Route::post('kyc/{id}/verify', [\App\Http\Controllers\Api\Admin\AdminController::class, 'verifyKyc']);
        Route::post('pickups/{id}/assign', [\App\Http\Controllers\Api\Admin\AdminController::class, 'assignPickup']);
        Route::post('pickups/{id}/reassign', [\App\Http\Controllers\Api\Admin\AdminController::class, 'reassignPickup']);
        Route::get('pickups/{id}/timeline', [\App\Http\Controllers\Api\Admin\AdminController::class, 'getPickupTimeline']);
        Route::get('pickups/{id}/assignment-history', [\App\Http\Controllers\Api\Admin\AdminController::class, 'getAssignmentHistory']);
        Route::get('pickups/{id}/verification', [\App\Http\Controllers\Api\Admin\AdminController::class, 'getVerificationAudit']);

        // Attributes
        Route::get('attributes', [\App\Http\Controllers\Api\Admin\AttributeController::class, 'index']);
        Route::post('attributes', [\App\Http\Controllers\Api\Admin\AttributeController::class, 'store']);
        Route::put('attributes/{id}', [\App\Http\Controllers\Api\Admin\AttributeController::class, 'update']);
        Route::delete('attributes/{id}', [\App\Http\Controllers\Api\Admin\AttributeController::class, 'destroy']);
        Route::post('attributes/{id}/assign', [\App\Http\Controllers\Api\Admin\AttributeController::class, 'assignToCategory']);

        // Settlements
        Route::get('settlements', [\App\Http\Controllers\Api\Admin\SettlementController::class, 'index']);
        Route::post('settlements', [\App\Http\Controllers\Api\Admin\SettlementController::class, 'store']);
        Route::get('settlements/partner/{partnerId}/summary', [\App\Http\Controllers\Api\Admin\SettlementController::class, 'partnerSummary']);
        Route::get('settlements/{id}', [\App\Http\Controllers\Api\Admin\SettlementController::class, 'show']);
        Route::put('settlements/{id}', [\App\Http\Controllers\Api\Admin\SettlementController::class, 'update']);
        Route::post('settlements/{id}/approve', [\App\Http\Controllers\Api\Admin\SettlementController::class, 'approve']);
        Route::post('settlements/{id}/mark-as-paid', [\App\Http\Controllers\Api\Admin\SettlementController::class, 'markAsPaid']);
        Route::post('settlements/{id}/reject', [\App\Http\Controllers\Api\Admin\SettlementController::class, 'reject']);

        // Warehouse Management
        Route::apiResource('warehouses', \App\Http\Controllers\Api\Admin\WarehouseManagementController::class);

        // Waitlist Management
        Route::get('waitlist', [\App\Http\Controllers\Api\Admin\WaitlistManagementController::class, 'index']);
        Route::get('waitlist/export', [\App\Http\Controllers\Api\Admin\WaitlistManagementController::class, 'export']);
        Route::post('waitlist/{id}/status', [\App\Http\Controllers\Api\Admin\WaitlistManagementController::class, 'updateStatus']);

        // Channel Partner Approval Workflow
        Route::prefix('partner-approvals')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\PartnerApprovalController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Api\Admin\PartnerApprovalController::class, 'show']);
            Route::post('/{id}/approve', [\App\Http\Controllers\Api\Admin\PartnerApprovalController::class, 'approve']);
            Route::post('/{id}/reject', [\App\Http\Controllers\Api\Admin\PartnerApprovalController::class, 'reject']);
        });

        // Channel Partner Management
        Route::prefix('channel-partners')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\PartnerApprovalController::class, 'listPartners']);
            Route::get('/{id}', [\App\Http\Controllers\Api\Admin\PartnerApprovalController::class, 'getPartnerDetail']);
            Route::get('/{id}/{type}', [\App\Http\Controllers\Api\Admin\PartnerApprovalController::class, 'getPartnerOversight']);
            Route::patch('/{id}/warehouse-limit', [\App\Http\Controllers\Api\Admin\PartnerApprovalController::class, 'updateWarehouseLimit']);
        });

        // Onboarding requests (Legacy/Unified)
        Route::get('onboarding/requests', [\App\Http\Controllers\Api\Admin\PartnerApprovalController::class, 'index']);
        Route::post('onboarding/requests/{id}/approve', [\App\Http\Controllers\Api\Admin\PartnerApprovalController::class, 'approve']);
        Route::post('onboarding/requests/{id}/reject', [\App\Http\Controllers\Api\Admin\PartnerApprovalController::class, 'reject']);
    });

    // Admin & Payment Admin Routes
    Route::prefix('admin')->middleware('role:admin|payment_admin')->group(function () {
        Route::get('payments', [\App\Http\Controllers\Api\Admin\AdminController::class, 'listPayments']);
        Route::get('payments/{id}', [\App\Http\Controllers\Api\Admin\AdminController::class, 'getPayment']);
        Route::post('payments/{id}/approve', [\App\Http\Controllers\Api\Admin\AdminController::class, 'approvePayment']);
        Route::get('withdrawals', [\App\Http\Controllers\Api\Admin\AdminController::class, 'listPayments']);
        Route::post('withdrawals/{id}/approve', [\App\Http\Controllers\Api\Admin\AdminController::class, 'approveWithdrawal']);
    });

});
