<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('frontend');
});

// Static pages mapped to frontend React app
Route::get('/privacy', function () {
    return view('privacy');
});
Route::get('/support', function () {
    return Inertia::render('Support');
})->name('support');
Route::get('/termscondition', function () {
    return view('frontend');
});
Route::get('/partner', function () {
    return view('frontend');
});
Route::get('/contact', function () {
    return view('frontend');
});
Route::get('/terms', function () {
    return view('frontend');
});
Route::get('/cancellation', function () {
    return view('frontend');
});

// Channel Partner Registration
Route::get('register/partner', [\App\Http\Controllers\Auth\PartnerRegistrationController::class, 'create'])->name('partner.register');
Route::post('register/partner', [\App\Http\Controllers\Auth\PartnerRegistrationController::class, 'store']);
Route::get('register/partner/success', function() {
    return Inertia::render('Auth/PartnerRegisterSuccess');
})->name('partner.register.success');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class , 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class , 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class , 'destroy'])->name('profile.destroy');
});

// Admin Routes (Now Root)
// Shared Authenticated Routes
Route::middleware(['auth', 'role:admin|warehouse|channel_partner|pickup_boy|customer'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class , 'index'])->name('dashboard');
});

// Admin ONLY Routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->names('admin.users');
    Route::resource('pickup-boys', \App\Http\Controllers\Admin\PickupBoyController::class)->names('admin.pickup-boys')->only(['index', 'show', 'update']);
    Route::post('users/{user}/addresses', [\App\Http\Controllers\Admin\UserAddressController::class , 'store'])->name('admin.users.addresses.store');
    Route::put('users/{user}/addresses/{address}', [\App\Http\Controllers\Admin\UserAddressController::class , 'update'])->name('admin.users.addresses.update');
    Route::delete('users/{user}/addresses/{address}', [\App\Http\Controllers\Admin\UserAddressController::class , 'destroy'])->name('admin.users.addresses.destroy');
    
    Route::get('/logs', [\App\Http\Controllers\Admin\LogController::class , 'index'])->name('admin.logs.index');

    // Product & System Management (with POST fallbacks to support seamless multipart image uploads)
    Route::post('categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'update']);
    Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class)->names('admin.categories');
    
    Route::post('category-types/{category_type}', [\App\Http\Controllers\Admin\CategoryTypeController::class, 'update']);
    Route::resource('category-types', \App\Http\Controllers\Admin\CategoryTypeController::class)->names('admin.category-types');
    
    Route::resource('attributes', \App\Http\Controllers\Admin\AttributeController::class)->names('admin.attributes');
    Route::resource('pages', \App\Http\Controllers\Admin\PageController::class)->names('admin.pages');
    
    // KYC & Locations
    Route::get('kyc', [\App\Http\Controllers\Admin\KycController::class , 'index'])->name('admin.kyc.index');
    Route::post('kyc/{kyc}/verify', [\App\Http\Controllers\Admin\KycController::class , 'verify'])->name('admin.kyc.verify');
    Route::get('locations', [\App\Http\Controllers\Admin\LocationController::class , 'index'])->name('admin.locations.index');
    Route::post('locations/states', [\App\Http\Controllers\Admin\LocationController::class , 'storeState'])->name('admin.locations.states.store');
    Route::post('locations/cities', [\App\Http\Controllers\Admin\LocationController::class , 'storeCity'])->name('admin.locations.cities.store');
    Route::put('locations/states/{state}', [\App\Http\Controllers\Admin\LocationController::class , 'updateState'])->name('admin.locations.states.update');
    Route::put('locations/cities/{city}', [\App\Http\Controllers\Admin\LocationController::class , 'updateCity'])->name('admin.locations.cities.update');

    // Multi-management
    Route::resource('warehouses', \App\Http\Controllers\Admin\WarehouseController::class)->names('admin.warehouses')->except(['index', 'show', 'update']);
    Route::get('partners', [\App\Http\Controllers\Admin\PartnerController::class , 'index'])->name('admin.partners.index');
    Route::get('partners/{partner}', [\App\Http\Controllers\Admin\PartnerController::class , 'show'])->name('admin.partners.show');

    // Waitlist Management
    Route::get('waitlist/export', [\App\Http\Controllers\Api\Admin\WaitlistManagementController::class, 'export'])->name('admin.waitlist.export');
    Route::resource('waitlist', \App\Http\Controllers\Admin\WaitlistController::class)->names('admin.waitlist')->only(['index', 'update', 'destroy']);

    // New Channel Partner Module
    Route::resource('channel-partners', \App\Http\Controllers\Admin\ChannelPartnerController::class)->names('admin.channel-partners');
    Route::post('channel-partners/{id}/approve', [\App\Http\Controllers\Admin\ChannelPartnerController::class, 'approve'])->name('admin.channel-partners.approve');
    Route::post('channel-partners/{id}/reject', [\App\Http\Controllers\Admin\ChannelPartnerController::class, 'reject'])->name('admin.channel-partners.reject');
    Route::post('channel-partners/{id}/fee-status', [\App\Http\Controllers\Admin\ChannelPartnerController::class, 'updateFeeStatus'])->name('admin.channel-partners.fee-status');

    // Mobile App Management
    Route::get('app-settings', [\App\Http\Controllers\Admin\AppSettingsController::class, 'index'])->name('admin.app-settings.index');
    Route::post('app-settings', [\App\Http\Controllers\Admin\AppSettingsController::class, 'update'])->name('admin.app-settings.update');

    Route::get('referrals', [\App\Http\Controllers\Admin\ReferralController::class, 'index'])->name('admin.referrals.index');
    Route::post('referrals/setting', [\App\Http\Controllers\Admin\ReferralController::class, 'storeSetting'])->name('admin.referrals.store-setting');
    Route::post('referrals/setting/{id}', [\App\Http\Controllers\Admin\ReferralController::class, 'updateSetting'])->name('admin.referrals.update-setting');
    Route::post('referrals/coupon/{id}/cancel', [\App\Http\Controllers\Admin\ReferralController::class, 'cancelCoupon'])->name('admin.referrals.cancel-coupon');

    Route::get('approval-requests', [\App\Http\Controllers\Admin\ApprovalRequestController::class, 'index'])->name('admin.approval-requests.index');
    Route::get('approval-requests/{id}', [\App\Http\Controllers\Admin\ApprovalRequestController::class, 'show'])->name('admin.approval-requests.show');
    Route::post('approval-requests/{id}/approve', [\App\Http\Controllers\Admin\ApprovalRequestController::class, 'approve'])->name('admin.approval-requests.approve');
    Route::post('approval-requests/{id}/reject', [\App\Http\Controllers\Admin\ApprovalRequestController::class, 'reject'])->name('admin.approval-requests.reject');

    Route::get('help-support', [\App\Http\Controllers\Admin\HelpSupportController::class, 'index'])->name('admin.help-support.index');
    Route::get('help-support/{id}', [\App\Http\Controllers\Admin\HelpSupportController::class, 'show'])->name('admin.help-support.show');
    Route::post('help-support/{id}/status', [\App\Http\Controllers\Admin\HelpSupportController::class, 'updateStatus'])->name('admin.help-support.update-status');
    Route::delete('help-support/{id}', [\App\Http\Controllers\Admin\HelpSupportController::class, 'destroy'])->name('admin.help-support.destroy');

    Route::get('withdrawals', [\App\Http\Controllers\Admin\WithdrawalController::class, 'index'])->name('admin.withdrawals.index');
    Route::post('withdrawals/{id}/approve', [\App\Http\Controllers\Admin\WithdrawalController::class, 'approve'])->name('admin.withdrawals.approve');
    Route::post('withdrawals/{id}/reject', [\App\Http\Controllers\Admin\WithdrawalController::class, 'reject'])->name('admin.withdrawals.reject');
    Route::post('pickups/auto-assign', [\App\Http\Controllers\Admin\PickupController::class, 'autoAssign'])->name('admin.pickups.auto-assign');
});

// Shared Business Entity Routes (Accessible by relevant roles + Admin)
Route::middleware(['auth', 'role:admin|warehouse|channel_partner|pickup_boy'])->group(function () {
    // Pickups (Bookings)
    Route::get('/pickups', [\App\Http\Controllers\Admin\PickupController::class , 'index'])->name('admin.pickups.index');
    Route::get('/pickups/{id}', [\App\Http\Controllers\Admin\PickupController::class , 'show'])->name('admin.pickups.show');
    Route::post('/pickups/{id}/assign', [\App\Http\Controllers\Admin\PickupController::class , 'assign'])->name('admin.pickups.assign');
    Route::post('/pickups/{id}/approve-reschedule', [\App\Http\Controllers\Admin\PickupController::class , 'approveReschedule'])->name('admin.pickups.approve-reschedule');
    Route::post('/pickups/{id}/reject-reschedule', [\App\Http\Controllers\Admin\PickupController::class , 'rejectReschedule'])->name('admin.pickups.reject-reschedule');
    Route::post('/pickups/{id}/submit-quote', [\App\Http\Controllers\Admin\PickupController::class , 'submitQuote'])->name('admin.pickups.submit-quote');
    Route::get('/partner-pickups/create', [\App\Http\Controllers\Admin\PartnerPickupController::class, 'create'])->name('admin.partner-pickups.create');
    Route::post('/partner-pickups', [\App\Http\Controllers\Admin\PartnerPickupController::class, 'store'])->name('admin.partner-pickups.store');
    Route::post('/pickups/{id}/deliver-to-warehouse', [\App\Http\Controllers\Admin\PartnerPickupController::class, 'deliverToWarehouse'])->name('admin.pickups.deliver-to-warehouse');

    // Contact Messages
    Route::get('contact-messages', [\App\Http\Controllers\Admin\ContactController::class, 'index'])->name('admin.contacts.index');
    Route::get('contact-messages/{contactMessage}', [\App\Http\Controllers\Admin\ContactController::class, 'show'])->name('admin.contacts.show');
    Route::patch('contact-messages/{contactMessage}/status', [\App\Http\Controllers\Admin\ContactController::class, 'updateStatus'])->name('admin.contacts.update-status');
    Route::delete('contact-messages/{contactMessage}', [\App\Http\Controllers\Admin\ContactController::class, 'destroy'])->name('admin.contacts.destroy');
});

Route::middleware(['auth', 'role:admin|channel_partner'])->group(function () {
    Route::resource('partner-customers', \App\Http\Controllers\Admin\PartnerCustomerController::class)
        ->except(['show'])
        ->parameters(['partner-customers' => 'partnerCustomer'])
        ->names('admin.partner-customers');
});

// Warehouse Specific Management
Route::middleware(['auth', 'role:admin|warehouse'])->group(function () {
    Route::get('warehouses', [\App\Http\Controllers\Admin\WarehouseController::class , 'index'])->name('admin.warehouses.index');
    Route::get('warehouses/{warehouse}', [\App\Http\Controllers\Admin\WarehouseController::class , 'show'])->name('admin.warehouses.show');
    Route::put('warehouses/{warehouse}', [\App\Http\Controllers\Admin\WarehouseController::class , 'update'])->name('admin.warehouses.update');

    // AJAX endpoints (Inertia/axios session-auth)
    Route::post('admin/location/reverse-geocode', [\App\Http\Controllers\Admin\WarehouseController::class, 'reverseGeocode'])->name('admin.warehouses.reverseGeocode');
    Route::get('admin/warehouses/{warehouse}/pickup-boys', [\App\Http\Controllers\Admin\WarehouseController::class, 'pickupBoys'])->name('admin.warehouses.pickupBoys');
    Route::post('admin/warehouses/{warehouse}/assign-pickup-boy', [\App\Http\Controllers\Admin\WarehouseController::class, 'attachPickupBoy'])->name('admin.warehouses.attachPickupBoy');
    Route::delete('admin/warehouses/{warehouse}/pickup-boys/{userId}', [\App\Http\Controllers\Admin\WarehouseController::class, 'detachPickupBoy'])->name('admin.warehouses.detachPickupBoy');
});

// Admin pickup price (web/axios)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::put('admin/pickups/{id}/final-price', [\App\Http\Controllers\Api\Admin\PickupPriceController::class, 'update'])->name('admin.pickups.updatePrice');
    Route::get('admin/pickups/{id}/price-logs', [\App\Http\Controllers\Api\Admin\PickupPriceController::class, 'logs'])->name('admin.pickups.priceLogs');
});

// Settlement Specific Management
Route::middleware(['auth', 'role:admin|channel_partner'])->group(function () {
    Route::get('settlements', [\App\Http\Controllers\Admin\SettlementController::class , 'index'])->name('admin.settlements.index');
    Route::get('settlements/{settlement}', [\App\Http\Controllers\Admin\SettlementController::class , 'show'])->name('admin.settlements.show');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('settlements/{settlement}/approve', [\App\Http\Controllers\Admin\SettlementController::class , 'approve'])->name('admin.settlements.approve');
    Route::post('settlements/{settlement}/payout-status', [\App\Http\Controllers\Admin\SettlementController::class , 'updatePayoutStatus'])->name('admin.settlements.updatePayoutStatus');
    Route::post('settlements/{settlement}/mark-as-paid', [\App\Http\Controllers\Admin\SettlementController::class , 'markAsPaid'])->name('admin.settlements.markAsPaid');
    Route::post('settlements/{settlement}/reject', [\App\Http\Controllers\Admin\SettlementController::class , 'reject'])->name('admin.settlements.reject');
});
 
// API Documentation
Route::get('/api/documentation', function () {
    return view('swagger');
});
 
require __DIR__ . '/auth.php';
