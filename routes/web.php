<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController; 
use App\Http\Controllers\PurchaseOrderController; 

Route::get('/', function () {
    return view('welcome');
});

// All routes inside this group will require the user to be logged in.
Route::middleware(['auth'])->group(function () {
    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Routes (不足していたルートを追加)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Inventory Routes
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/create', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{inventory}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
    Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

    // Sales Routes
    Route::post('/sales', [SalesController::class, 'store'])->name('sales.store');

    // Forecast Routes
    Route::get('/forecast', [ForecastController::class, 'index'])->name('forecast.index');
    Route::post('/forecast', [ForecastController::class, 'predict'])->name('forecast.predict');
    Route::post('/forecast', [ForecastController::class, 'runBatchForecast'])->name('forecast.run');


    // Product Management Routes
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');

    // User Management Routes
    Route::get('/users', [UserController::class, 'index'])->name('users.index');

    Route::get('/dashboard/sales-details', [DashboardController::class, 'getSalesDetailsForPeriod'])->name('dashboard.salesDetails');
    
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');

    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');

});


// This line is automatically added by Breeze for its own routes (login, register, etc.)
require __DIR__.'/auth.php';