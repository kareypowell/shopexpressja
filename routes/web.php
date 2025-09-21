<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Livewire\Auth\{Login, Register, Verify};
use App\Http\Livewire\Auth\Passwords\{Confirm, Email, Reset};
use App\Http\Livewire\Customers\AdminCustomer;
use App\Http\Livewire\Customers\{CustomerCreate, CustomerProfile, CustomerEdit};
use App\Http\Livewire\Admin\CustomerBalanceManager;
use App\Http\Livewire\Users\{UserManagement, UserCreate, UserEdit};
use App\Http\Controllers\UserController;
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
    

    
    // Audit log management routes - accessible only by superadmin
    Route::get('/audit-logs', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('admin.audit-logs.index')->middleware('admin.restriction');
    Route::get('/audit-logs/download/{filename}', [App\Http\Controllers\Admin\AuditLogController::class, 'download'])->name('admin.audit-logs.download')->middleware('admin.restriction');
    
    // Security monitoring dashboard - accessible only by superadmin
    Route::get('/security-dashboard', \App\Http\Livewire\Admin\SecurityDashboard::class)->name('security-dashboard')->middleware('admin.restriction');

    // Backup management routes - accessible only by superadmin
    Route::get('/backup-dashboard', \App\Http\Livewire\Admin\BackupDashboard::class)->name('backup-dashboard')->middleware('admin.restriction');
    Route::get('/backup-history', \App\Http\Livewire\Admin\BackupHistory::class)->name('backup-history')->middleware('admin.restriction');
    Route::get('/backup-settings', \App\Http\Livewire\Admin\BackupSettings::class)->name('backup-settings')->middleware('admin.restriction');
    Route::get('/backup/{backup}/download', [App\Http\Controllers\BackupController::class, 'download'])->name('backup.download')->middleware('admin.restriction');
    // Role management routes - accessible only by superadmin
    Route::get('/roles', Role::class)->name('admin.roles')->middleware(['can:role.viewAny', 'admin.restriction']);


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
    
    // Customer balance management
    Route::get('/customers/{customer}/balance', CustomerBalanceManager::class)->name('customers.balance');
});

// User Management routes (both superadmin and admin can access)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // User management routes - accessible by both admin and superadmin with policy checks
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', UserManagement::class)->name('index')->middleware('can:user.viewAny');
        Route::get('/create', UserCreate::class)->name('create')->middleware('can:user.create');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', UserEdit::class)->name('edit');
    });
});

// Admin routes (both superadmin and admin can access)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Broadcast messaging routes - accessible by both admin and superadmin
    Route::prefix('broadcast-messages')->name('broadcast-messages.')->group(function () {
        Route::get('/', [App\Http\Controllers\BroadcastMessageController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\BroadcastMessageController::class, 'create'])->name('create');
        Route::get('/{broadcastMessage}', [App\Http\Controllers\BroadcastMessageController::class, 'show'])->name('show');
    });
    
    // Office management routes - accessible by both admin and superadmin
    Route::prefix('offices')->name('offices.')->group(function () {
        Route::get('/', [\App\Http\Controllers\OfficeController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\OfficeController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\OfficeController::class, 'store'])->name('store');
        Route::get('/{office}', [\App\Http\Controllers\OfficeController::class, 'show'])->name('show');
        Route::get('/{office}/edit', [\App\Http\Controllers\OfficeController::class, 'edit'])->name('edit');
        Route::put('/{office}', [\App\Http\Controllers\OfficeController::class, 'update'])->name('update');
        Route::delete('/{office}', [\App\Http\Controllers\OfficeController::class, 'destroy'])->name('destroy');
    });
    
    // Address management routes - accessible by both admin and superadmin
    Route::prefix('addresses')->name('addresses.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AddressController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\AddressController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\AddressController::class, 'store'])->name('store');
        Route::get('/{address}', [\App\Http\Controllers\AddressController::class, 'show'])->name('show');
        Route::get('/{address}/edit', [\App\Http\Controllers\AddressController::class, 'edit'])->name('edit');
        Route::put('/{address}', [\App\Http\Controllers\AddressController::class, 'update'])->name('update');
        Route::delete('/{address}', [\App\Http\Controllers\AddressController::class, 'destroy'])->name('destroy');
    });
});

// Admin and Operations routes (both superadmin and admin can access)
Route::middleware(['auth', 'verified', 'admin.access'])->prefix('admin')->group(function () {
    // Dashboard - accessible by both admin and superadmin
    Route::get('/dashboard', \App\Http\Livewire\AdminDashboard::class)->name('admin.dashboard');
    
    // Operations routes - accessible by both admin and superadmin
    Route::get('/package-distribution', \App\Http\Livewire\PackageDistribution::class)->name('package-distribution');
    
    // Manifest routes with new navigation structure
    Route::prefix('manifests')->name('admin.manifests.')->group(function () {
        Route::get('/', Manifest::class)->name('index');
        Route::get('/create', Manifest::class)->name('create');
        Route::get('/{manifest}/edit', EditManifest::class)->name('edit');
        Route::get('/{manifest}/packages', ManifestPackage::class)->name('packages');
        Route::get('/{manifest}/packages/{package}/edit', EditManifestPackage::class)->name('packages.edit');
        
        // Package workflow routes
        Route::get('/{manifest}/workflow', \App\Http\Livewire\Manifests\PackageWorkflow::class)->name('workflow');
        Route::get('/{manifest}/distribution', \App\Http\Livewire\Manifests\PackageDistribution::class)->name('distribution');
    });
    
    // Financial routes - accessible by both admin and superadmin
    Route::get('/transactions', \App\Http\Livewire\Admin\TransactionManagement::class)->name('transactions');
    Route::get('/rates', Rate::class)->name('view-rates');
    Route::get('/purchase-requests', AdminPurchaseRequest::class)->name('view-purchase-requests');
    Route::get('/pre-alerts', AdminPreAlert::class)->name('view-pre-alerts');

});

// Customer routes
Route::middleware(['auth', 'verified', 'role:customer'])->group(function () {
    Route::get('/packages', \App\Http\Livewire\Customers\CustomerPackages::class)->name('packages.index');
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

// Debug routes (only in non-production environments)
if (app()->environment(['local', 'testing'])) {
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/test-consolidation-toggle', function () {
            return view('test.consolidation-toggle');
        })->name('test.consolidation-toggle');
        
        Route::get('/test-html5-editor', function () {
            return view('test-editor');
        })->name('test.html5-editor');
        


    });
}