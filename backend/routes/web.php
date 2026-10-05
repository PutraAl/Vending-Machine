<?php

use App\Http\Controllers\Web\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\MachineController;
use App\Http\Controllers\Web\MachineSlotController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // CRUD KATEGORI

    Route::get('/categories', [CategoryController::class, 'index'])
        ->name('categories.index');

    Route::post('/categories', [CategoryController::class, 'store'])
        ->name('categories.store');

    Route::put('/categories/{category}', [CategoryController::class, 'update'])
        ->name('categories.update');

    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->name('categories.destroy');

    // cruddd produkk
    Route::get('/products', [ProductController::class, 'index'])
        ->name('products.index');

    Route::post('/products', [ProductController::class, 'store'])
        ->name('products.store');

    Route::put('/products/{product}', [ProductController::class, 'update'])
        ->name('products.update');

    Route::delete('/products/{product}', [ProductController::class, 'destroy'])
        ->name('products.destroy');

    // CRUD MESIN 
    Route::get('/machines', [MachineController::class, 'index'])
        ->name('machines.index');

    Route::post('/machines', [MachineController::class, 'store'])
        ->name('machines.store');

    Route::put('/machines/{machine}', [MachineController::class, 'update'])
        ->name('machines.update');

    Route::delete('/machines/{machine}', [MachineController::class, 'destroy'])
        ->name('machines.destroy');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // SLOT
    Route::get('/stock-slots', [MachineSlotController::class, 'index'])
        ->name('stock-slots.index');

    Route::post('/stock-slots', [MachineSlotController::class, 'store'])
        ->name('stock-slots.store');

    Route::put('/stock-slots/{slot}', [MachineSlotController::class, 'update'])
        ->name('stock-slots.update');

    Route::delete('/stock-slots/{slot}', [MachineSlotController::class, 'destroy'])
        ->name('stock-slots.destroy');
});
