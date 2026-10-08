<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DispenseController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\MachineSlotController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\PaymentController;

Route::prefix('v1')->group(function (): void {

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->group(function (): void {
        Route::post('/login', [
            AuthController::class,
            'login',
        ]);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [
                AuthController::class,
                'logout',
            ]);

            Route::get('/me', [
                AuthController::class,
                'me',
            ]);
        });
    });

    /*
|--------------------------------------------------------------------------
| Payment
|--------------------------------------------------------------------------
*/

    Route::post('/payments/simulate', [
        PaymentController::class,
        'simulate',
    ]);

    Route::post('/payments/{payment}/confirm-dispense', [
        PaymentController::class,
        'confirmDispense',
    ]);

    /*
    |--------------------------------------------------------------------------
    | PUBLIC / KIOSK API
    |--------------------------------------------------------------------------
    */

    // Inventory for kiosk
    Route::get('/inventory', [
        InventoryController::class,
        'index',
    ]);
    // Product catalog for kiosk
    Route::get('/products', [
        ProductController::class,
        'index',
    ]);

    // Create order from kiosk
    Route::post('/orders', [
        OrderController::class,
        'store',
    ]);

    /*
    |--------------------------------------------------------------------------
    | PROTECTED API
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function (): void {

        // Categories
        Route::get('/categories', [
            ProductCategoryController::class,
            'index',
        ])->middleware('role:admin,technician,operator');

        Route::post('/categories', [
            ProductCategoryController::class,
            'store',
        ])->middleware('role:admin,operator');

        Route::get('/categories/{category}', [
            ProductCategoryController::class,
            'show',
        ])->middleware('role:admin,technician,operator');

        Route::put('/categories/{category}', [
            ProductCategoryController::class,
            'update',
        ])->middleware('role:admin,operator');

        Route::delete('/categories/{category}', [
            ProductCategoryController::class,
            'destroy',
        ])->middleware('role:admin');

        // Product management
        Route::post('/products', [
            ProductController::class,
            'store',
        ])->middleware('role:admin,operator');

        Route::get('/products/{product}', [
            ProductController::class,
            'show',
        ])->middleware('role:admin,technician,operator');

        Route::put('/products/{product}', [
            ProductController::class,
            'update',
        ])->middleware('role:admin,operator');

        Route::delete('/products/{product}', [
            ProductController::class,
            'destroy',
        ])->middleware('role:admin');

        // Machines
        Route::get('/machines', [
            MachineController::class,
            'index',
        ])->middleware('role:admin,technician,operator');

        Route::post('/machines', [
            MachineController::class,
            'store',
        ])->middleware('role:admin');

        Route::get('/machines/{machine}', [
            MachineController::class,
            'show',
        ])->middleware('role:admin,technician,operator');

        Route::put('/machines/{machine}', [
            MachineController::class,
            'update',
        ])->middleware('role:admin');

        Route::delete('/machines/{machine}', [
            MachineController::class,
            'destroy',
        ])->middleware('role:admin');

        // Machine slots
        Route::scopeBindings()->group(function (): void {

            Route::get('/machines/{machine}/slots', [
                MachineSlotController::class,
                'index',
            ])->middleware('role:admin,technician,operator');

            Route::post('/machines/{machine}/slots', [
                MachineSlotController::class,
                'store',
            ])->middleware('role:admin,operator');

            Route::get('/machines/{machine}/slots/{slot}', [
                MachineSlotController::class,
                'show',
            ])->middleware('role:admin,technician,operator');

            Route::put('/machines/{machine}/slots/{slot}', [
                MachineSlotController::class,
                'update',
            ])->middleware('role:admin,operator');

            Route::delete('/machines/{machine}/slots/{slot}', [
                MachineSlotController::class,
                'destroy',
            ])->middleware('role:admin');

            Route::patch('/machines/{machine}/slots/{slot}/stock', [
                MachineSlotController::class,
                'updateStock',
            ])->middleware('role:admin,operator');
        });

        // Order management
        Route::get('/orders', [
            OrderController::class,
            'index',
        ])->middleware('role:admin,technician,operator');

        Route::get('/orders/{order}', [
            OrderController::class,
            'show',
        ])->middleware('role:admin,technician,operator');

        // Dispense result
        Route::post('/dispenses/{dispense}/complete', [
            DispenseController::class,
            'complete',
        ])->middleware('role:admin,operator');

        Route::post('/dispenses/{dispense}/fail', [
            DispenseController::class,
            'fail',
        ])->middleware('role:admin,operator');
    });

  
  
});
