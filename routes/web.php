<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EwalletController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified', 'store'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports/utang.pdf', [ReportController::class, 'exportUtangPdf'])->name('reports.utang.pdf');
    Route::get('reports/utang/{customer}.pdf', [ReportController::class, 'exportUtangCustomerPdf'])->name('reports.utang.customer-pdf');

    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
    Route::post('stores', [StoreController::class, 'store'])->name('stores.store');
    Route::put('stores/{store}', [StoreController::class, 'update'])->name('stores.update');
    Route::post('stores/{store}/switch', [StoreController::class, 'switch'])->name('stores.switch');

    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('products/{product}/stock', [ProductController::class, 'adjustStock'])->name('products.stock');

    Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('sales/pos', [SaleController::class, 'create'])->name('sales.pos');
    Route::post('sales', [SaleController::class, 'store'])->name('sales.store');

    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('customers/{customer}/pay', [CustomerController::class, 'pay'])->name('customers.pay');

    Route::get('ewallet', [EwalletController::class, 'index'])->name('ewallet.index');
    Route::post('ewallet/providers', [EwalletController::class, 'storeProvider'])->name('ewallet.providers.store');
    Route::post('ewallet/transactions', [EwalletController::class, 'storeTransaction'])->name('ewallet.transactions.store');

    Route::get('team', [TeamController::class, 'index'])->name('team.index');
    Route::post('team', [TeamController::class, 'store'])->name('team.store');
    Route::put('team/{user}/role', [TeamController::class, 'updateRole'])->name('team.role');
});

require __DIR__.'/settings.php';
