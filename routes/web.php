<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Livewire\Auth\{Login, Register, Verify};
use App\Http\Livewire\Auth\Passwords\{Confirm, Email, Reset};
use App\Http\Livewire\Customers\AdminCustomer;
use App\Http\Livewire\Customers\{CustomerCreate, CustomerProfile, CustomerEdit};
use Illuminate\Support\Facades\Route;
use App\Http\Livewire\{Dashboard, Invoice};
use App\Http\Livewire\Manifests\Manifest;
use App\Http\Livewire\Manifests\Packages\ManifestPackage;
use App\Http\Livewire\PreAlerts\{PreAlert, AdminPreAlert, ViewPreAlert};
use App\Http\Livewire\PurchaseRequests\{PurchaseRequest, AdminPurchaseRequest};
use App\Http\Livewire\Rates\Rate;
use App\Http\Livewire\Profile\Profile;
use App\Http\Livewire\Roles\Role;
use App\Http\Livewire\Customers\ShippingInformation;
use App\Http\Livewire\Manifests\EditManifest;
use App\Http\Livewire\Manifests\Packages\EditManifestPackage;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->middleware('auth', 'verified')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)
        ->name('login');

    Route::get('register', Register::class)
        ->name('register');
});

Route::get('password/reset', Email::class)
    ->name('password.request');

Route::get('password/reset/{token}', Reset::class)
    ->name('password.reset');

Route::middleware('auth')->group(function () {
    Route::get('email/verify', Verify::class)
        ->middleware('throttle:6,1')
        ->name('verification.notice');

    Route::get('password/confirm', Confirm::class)
        ->name('password.confirm');
});

Route::middleware('auth')->group(function () {
    Route::get('email/verify/{id}/{hash}', EmailVerificationController::class)
        ->middleware('signed')
        ->name('verification.verify');

    Route::post('logout', LogoutController::class)
        ->name('logout');
});



Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Super Admin routes
Route::middleware(['auth', 'verified', 'role:superadmin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', \App\Http\Livewire\AdminDashboard::class)->name('admin.dashboard');
    Route::get('/manifests', Manifest::class)->name('manifests');
    Route::get('/manifests/{manifest_id}/edit', EditManifest::class)->name('edit-manifest');
    Route::get('/manifests/{manifest_id}/packages', ManifestPackage::class)->name('manifests.packages');
    Route::get('/manifests/{manifest_id}/packages/{package_id}/edit', EditManifestPackage::class)->name('manifests.packages.edit');
    Route::get('/roles', Role::class)->name('roles');
    Route::get('/rates', Rate::class)->name('view-rates');
    Route::get('/pre-alerts', AdminPreAlert::class)->name('view-pre-alerts');
    Route::get('/purchase-requests', AdminPurchaseRequest::class)->name('view-purchase-requests');
});

// Customer Management routes (both superadmin and admin can access)
Route::middleware(['auth', 'verified', 'customer.management'])->prefix('admin')->name('admin.')->group(function () {
    // Customer listing and management
    Route::get('/customers', AdminCustomer::class)->name('customers.index');
    
    // Customer creation
    Route::get('/customers/create', CustomerCreate::class)->name('customers.create');
    
    // Customer profile viewing with route model binding
    Route::get('/customers/{customer}', CustomerProfile::class)->name('customers.show');
    
    // Customer editing with route model binding
    Route::get('/customers/{customer}/edit', CustomerEdit::class)->name('customers.edit');
});

// Customer routes
Route::middleware(['auth', 'verified', 'role:customer'])->group(function () {
    Route::get('/invoices', Invoice::class)->name('invoices');
    Route::get('/my-profile', Profile::class)->name('my-profile');
    Route::get('/shipping-information', ShippingInformation::class)->name('shipping-information');
    Route::get('/pre-alerts', PreAlert::class)->name('pre-alerts');
    Route::get('/pre-alerts/{pre_alert_id}/view', ViewPreAlert::class)->name('view-pre-alert');
    Route::get('/purchase-requests', PurchaseRequest::class)->name('purchase-requests');
    Route::get('/purchase-requests/{purchase_request_id}/view', PurchaseRequest::class)->name('view-purchase-request');
    Route::get('/rates', Rate::class)->name('rates');
});

// Purchaser routes
Route::middleware(['auth', 'verified', 'role:purchaser'])->prefix('staff')->group(function () {
    // Route::get('/rates', Rate::class)->name('rates');
});