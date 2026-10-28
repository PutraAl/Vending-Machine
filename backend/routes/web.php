<?php

use App\Http\Controllers\Web\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\MachineController;
use App\Http\Controllers\Web\MachineSlotController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\DispensingController;
use App\Http\Controllers\Web\TelemetryController;
use App\Http\Controllers\Web\MachineErrorController;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/ping-api', fn() => response()->json([
    'routes' => collect(app('router')->getRoutes())->map->uri()->values(),
]));
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

    // SLOT & STOCK: pilih mesin
    Route::get('/stock-slots', [MachineSlotController::class, 'index'])
        ->name('stock-slots.index');

    // SLOT & STOCK: detail mesin terpilih
    Route::get('/stock-slots/machines/{machine}', [MachineSlotController::class, 'showMachine'])
        ->name('stock-slots.machine');

    // Tambah slot khusus mesin terpilih
    Route::post('/stock-slots/machines/{machine}', [MachineSlotController::class, 'storeForMachine'])
        ->name('stock-slots.machine.store');

    // Edit dan hapus slot yang sudah ada
    Route::put('/stock-slots/{slot}', [MachineSlotController::class, 'update'])
        ->name('stock-slots.update');

    Route::delete('/stock-slots/{slot}', [MachineSlotController::class, 'destroy'])
        ->name('stock-slots.destroy');

    // Liat data order
    Route::get('/orders', [OrderController::class, 'index'])
        ->name('orders.index');

    // lIat data dispense
    Route::get('/dispensing', [DispensingController::class, 'index'])
        ->name('dispensing.index');

    // Liat data telemetry
    Route::get('/telemetry', [TelemetryController::class, 'index'])
        ->name('telemetry.index');

    // Liat data error mesin
    Route::get('/machine-errors', [MachineErrorController::class, 'index'])
        ->name('machine-errors.index');
    Route::post(
        '/machine-errors/{machineError}/resolve',
        [MachineErrorController::class, 'resolve']
    )->name('machine-errors.resolve');
});
