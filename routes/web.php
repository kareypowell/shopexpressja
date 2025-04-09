<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Livewire\Auth\Login;
use App\Http\Livewire\Auth\Passwords\Confirm;
use App\Http\Livewire\Auth\Passwords\Email;
use App\Http\Livewire\Auth\Passwords\Reset;
use App\Http\Livewire\Auth\Register;
use App\Http\Livewire\Auth\Verify;
use App\Http\Livewire\Customers\AdminCustomer;
use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Dashboard;
use App\Http\Livewire\Invoice;
use App\Http\Livewire\Manifests\Manifest;
use App\Http\Livewire\Manifests\Packages\ManifestPackage;
use App\Http\Livewire\PreAlerts\PreAlert;
use App\Http\Livewire\PreAlerts\AdminPreAlert;
use App\Http\Livewire\PurchaseRequests\PurchaseRequest;
use App\Http\Livewire\PurchaseRequests\AdminPurchaseRequest;
use App\Http\Livewire\Rates\Rate;
use App\Http\Livewire\Profile\Profile;
use App\Http\Livewire\Roles\Role;
use App\Http\Livewire\Customers\ShippingInformation;
use App\Http\Livewire\ViewPreAlert;
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

Route::get('/', Dashboard::class)->middleware('auth', 'verified')->name('home');

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

Route::get('/email/verify', function () {
    return view('livewire.auth.verify');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    // Mail::to(auth()->user()->email)->send(new WelcomeUser(auth()->user()->first_name));

    return redirect('/');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Super Admin routes
Route::middleware(['auth', 'verified', 'role:superadmin'])->prefix('admin')->group(function () {
    Route::get('/customers', AdminCustomer::class)->name('customers');
    Route::get('/manifests', Manifest::class)->name('manifests');
    Route::get('/manifests/{manifest_id}/packages', ManifestPackage::class)->name('manifests.packages');
    Route::get('/roles', Role::class)->name('roles');
    Route::get('/rates', Rate::class)->name('rates');
    Route::get('/pre-alerts', AdminPreAlert::class)->name('pre-alerts');
    Route::get('/purchase-requests', AdminPurchaseRequest::class)->name('purchase-requests');
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

// Admin routes
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    // Route::get('/rates', Rate::class)->name('rates');
});

// Purchaser routes
Route::middleware(['auth', 'verified', 'role:purchaser'])->prefix('staff')->group(function () {
    // Route::get('/rates', Rate::class)->name('rates');
});