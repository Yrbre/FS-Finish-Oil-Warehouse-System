<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Master Data
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::middleware('can:manage-departments')->group(function () {
        Route::resource('departments', DepartmentController::class)->except('show');
    });

    Route::middleware('can:manage-warehouses')->group(function () {
        Route::resource('warehouses', WarehouseController::class)->except('show');
    });

    Route::middleware('can:manage-items')->group(function () {
        // Route spesifik sebelum resource supaya tidak ketabrak pola items/{item}
        Route::get('items/get-stock', [ItemController::class, 'getStock'])->name('items.get-stock');
        Route::get('items/{id}/detail', [ItemController::class, 'detail'])->name('items.detail');

        Route::resource('items', ItemController::class)->except('show');
    });
});

/*
|--------------------------------------------------------------------------
| Modul berikutnya — buka begitu controllernya sudah dibuat.
| Sengaja disiapkan agar navbar tidak error "Route not defined".
|--------------------------------------------------------------------------
*/

// Route::middleware(['auth', 'can:manage-item-locations'])->group(function () {
//     Route::resource('item-locations', ItemLocationController::class)->except('show');
// });

// Route::middleware(['auth', 'can:create-transaction'])->group(function () {
//     Route::get('transactions/get-lots', [TransactionController::class, 'getLots'])->name('transactions.get-lots');
//     Route::get('transactions/get-stock', [TransactionController::class, 'getStock'])->name('transactions.get-stock');
//     Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store', 'destroy']);
// });

// Route::middleware(['auth', 'can:manage-transfer-request'])->group(function () {
//     Route::resource('transfer-requests', TransferRequestController::class);
//     Route::post('transfer-requests/{id}/approve', [TransferRequestController::class, 'approve'])->name('transfer-requests.approve');
//     Route::post('transfer-requests/{id}/receive', [TransferRequestController::class, 'receive'])->name('transfer-requests.receive');
//     Route::post('transfer-requests/{id}/reject', [TransferRequestController::class, 'reject'])->name('transfer-requests.reject');
//     Route::post('transfer-requests/{id}/cancel', [TransferRequestController::class, 'cancel'])->name('transfer-requests.cancel');
// });

// Route::middleware(['auth', 'can:view-reports'])->group(function () {
//     Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
// });

// Route::middleware(['auth', 'can:manage-users'])->group(function () {
//     Route::resource('users', UserController::class)->except('show');
// });

// Route::middleware(['auth', 'can:manage-roles'])->group(function () {
//     Route::resource('roles', RoleController::class)->except('show');
// });

require __DIR__ . '/auth.php';
