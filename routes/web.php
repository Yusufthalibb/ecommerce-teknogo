<?php

use Illuminate\Support\Facades\Route;

// auth 
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ProfileController;

// superadmin
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\CategoryController;
use App\Http\Controllers\SuperAdmin\UserController;
use App\Http\Controllers\SuperAdmin\PaymentVerificationController;
use App\Http\Controllers\SuperAdmin\OrderController as SuperAdminOrderController;
use App\Http\Controllers\SuperAdmin\ReportController;

// admin
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;

// customer
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Customer\WishlistController;
use App\Http\Controllers\Customer\ReviewController;

// Guest 
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});


// Public,bisa di akses semua role
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [CustomerProductController::class, 'index'])->name('index');
    Route::get('/search', [CustomerProductController::class, 'search'])->name('search');
    Route::get('/{slug}', [CustomerProductController::class, 'show'])->name('show');
});

// authenticated (Perlu Login)

Route::middleware(['auth'])->group(function () {
    
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [ProfileController::class, 'update'])->name('update');
        Route::get('/password', [ProfileController::class, 'changePassword'])->name('password');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    });
    
// Customer routes
    
    Route::middleware(['role:customer'])->group(function () {
        
        // Cart
        Route::prefix('cart')->name('cart.')->group(function () {
            Route::get('/', [CartController::class, 'index'])->name('index');
            Route::post('/add', [CartController::class, 'add'])->name('add');
            Route::put('/{cart}', [CartController::class, 'update'])->name('update');
            Route::delete('/{cart}', [CartController::class, 'destroy'])->name('destroy');
            Route::delete('/', [CartController::class, 'clear'])->name('clear');
        });
        
        // Checkout
        Route::prefix('checkout')->name('checkout.')->group(function () {
            Route::get('/', [CheckoutController::class, 'index'])->name('index');
            Route::post('/', [CheckoutController::class, 'store'])->name('store');
        });
        
        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [CustomerOrderController::class, 'index'])->name('index');
            Route::get('/{orderNumber}', [CustomerOrderController::class, 'show'])->name('show');
            Route::post('/{order}/cancel', [CustomerOrderController::class, 'cancel'])->name('cancel');
        });
        
        // Payment
        Route::prefix('payment')->name('payment.')->group(function () {
            Route::get('/{orderNumber}', [PaymentController::class, 'show'])->name('show');
            Route::post('/{order}/upload', [PaymentController::class, 'upload'])->name('upload');
            Route::post('/{order}/reupload', [PaymentController::class, 'reupload'])->name('reupload');
        });
        
        // Wishlist
        Route::prefix('wishlist')->name('wishlist.')->group(function () {
            Route::get('/', [WishlistController::class, 'index'])->name('index');
            Route::post('/toggle', [WishlistController::class, 'toggle'])->name('toggle');
            Route::delete('/{wishlist}', [WishlistController::class, 'destroy'])->name('destroy');
        });
        
        // Reviews
        Route::prefix('reviews')->name('reviews.')->group(function () {
            Route::get('/create/{orderNumber}/{productId}', [ReviewController::class, 'create'])->name('create');
            Route::post('/', [ReviewController::class, 'store'])->name('store');
            Route::get('/{review}/edit', [ReviewController::class, 'edit'])->name('edit');
            Route::put('/{review}', [ReviewController::class, 'update'])->name('update');
            Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');
        });
    });
    
   // Admin routes
    
    Route::prefix('admin')->name('admin.')->middleware(['role:admin'])->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        
        // Products
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [AdminProductController::class, 'index'])->name('index');
            Route::get('/create', [AdminProductController::class, 'create'])->name('create');
            Route::post('/', [AdminProductController::class, 'store'])->name('store');
            Route::get('/{product}', [AdminProductController::class, 'show'])->name('show');
            Route::get('/{product}/edit', [AdminProductController::class, 'edit'])->name('edit');
            Route::put('/{product}', [AdminProductController::class, 'update'])->name('update');
            Route::delete('/{product}', [AdminProductController::class, 'destroy'])->name('destroy');
            Route::post('/{product}/toggle-status', [AdminProductController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{product}/toggle-featured', [AdminProductController::class, 'toggleFeatured'])->name('toggle-featured');
        });
        
        // Product Images
        Route::prefix('products/{product}/images')->name('products.images.')->group(function () {
            Route::get('/', [ProductImageController::class, 'index'])->name('index');
            Route::post('/', [ProductImageController::class, 'store'])->name('store');
        });
        Route::prefix('images')->name('images.')->group(function () {
            Route::post('/{image}/primary', [ProductImageController::class, 'setPrimary'])->name('set-primary');
            Route::delete('/{image}', [ProductImageController::class, 'destroy'])->name('destroy');
        });
        
        // Stock Management
        Route::prefix('stock')->name('stock.')->group(function () {
            Route::get('/', [StockController::class, 'index'])->name('index');
            Route::put('/{product}', [StockController::class, 'update'])->name('update');
            Route::post('/bulk-update', [StockController::class, 'bulkUpdate'])->name('bulk-update');
        });
        
        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [AdminOrderController::class, 'index'])->name('index');
            Route::get('/{order}', [AdminOrderController::class, 'show'])->name('show');
            Route::put('/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('update-status');
        });
    });
    
    // Super Admin routes
    
    Route::prefix('super-admin')->name('super-admin.')->middleware(['role:super_admin'])->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
        
        // Categories
        Route::resource('categories', CategoryController::class);
        Route::post('/categories/{category}/toggle', [CategoryController::class, 'toggleStatus'])->name('categories.toggle');
        
        // Users
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            Route::post('/{user}/toggle', [UserController::class, 'toggleStatus'])->name('toggle');
            Route::put('/{user}/role', [UserController::class, 'changeRole'])->name('change-role');
        });
        
        // Payment Verification
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [PaymentVerificationController::class, 'index'])->name('index');
            Route::get('/{payment}', [PaymentVerificationController::class, 'show'])->name('show');
            Route::post('/{payment}/verify', [PaymentVerificationController::class, 'verify'])->name('verify');
            Route::post('/{payment}/reject', [PaymentVerificationController::class, 'reject'])->name('reject');
        });
        
        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [SuperAdminOrderController::class, 'index'])->name('index');
            Route::get('/{order}', [SuperAdminOrderController::class, 'show'])->name('show');
            Route::put('/{order}/status', [SuperAdminOrderController::class, 'updateStatus'])->name('update-status');
            Route::delete('/{order}', [SuperAdminOrderController::class, 'destroy'])->name('destroy');
        });
        
        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('/products', [ReportController::class, 'products'])->name('products');
            Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
            Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue');
            Route::post('/export', [ReportController::class, 'export'])->name('export');
        });
    });
});