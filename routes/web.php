<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemLocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransferRequestController;
use App\Http\Controllers\UserController;
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

Route::middleware(['auth', 'can:manage-item-locations'])->group(function () {
    Route::resource('item-locations', ItemLocationController::class)->except('show');
});



// Transaction routes
Route::middleware(['auth', 'can:create-transaction'])->group(function () {

    // Daftar semua transaksi
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');

    // AJAX helpers
    Route::get('transactions/get-stock', [TransactionController::class, 'getStock'])->name('transactions.get-stock');
    Route::get('transactions/get-lots', [TransactionController::class, 'getLots'])->name('transactions.get-lots');

    // PORC — Supply Oil
    Route::get('transactions/supply-oil', [TransactionController::class, 'createPorc'])->name('transactions.porc.create');
    Route::post('transactions/supply-oil', [TransactionController::class, 'storePorc'])->name('transactions.porc.store');

    // CONS — Pemakaian
    Route::get('transactions/pemakaian', [TransactionController::class, 'createCons'])->name('transactions.cons.create');
    Route::post('transactions/pemakaian', [TransactionController::class, 'storeCons'])->name('transactions.cons.store');

    // ADJ — Adjustment
    Route::get('transactions/adjustment', [TransactionController::class, 'createAdj'])->name('transactions.adj.create');
    Route::post('transactions/adjustment', [TransactionController::class, 'storeAdj'])->name('transactions.adj.store');

    // Hapus (hanya PORC)
    Route::delete('transactions/{id}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
});

Route::middleware(['auth'])->group(function () {

    // manage-transfer-request: bisa buat & lihat request sendiri
    // approve-transfer: bisa approve/reject (IMC)
    // receive-transfer: bisa konfirmasi terima
    Route::middleware('can:manage-transfer-request')->group(function () {
        Route::get('transfer-requests', [TransferRequestController::class, 'index'])->name('transfer-requests.index');
        Route::get('transfer-requests/create', [TransferRequestController::class, 'create'])->name('transfer-requests.create');
        Route::post('transfer-requests', [TransferRequestController::class, 'store'])->name('transfer-requests.store');
        Route::get('transfer-requests/{id}', [TransferRequestController::class, 'show'])->name('transfer-requests.show');
        Route::post('transfer-requests/{id}/cancel', [TransferRequestController::class, 'cancel'])->name('transfer-requests.cancel');
    });

    Route::middleware('can:approve-transfer')->group(function () {
        Route::post('transfer-requests/{id}/approve', [TransferRequestController::class, 'approve'])->name('transfer-requests.approve');
        Route::post('transfer-requests/{id}/reject', [TransferRequestController::class, 'reject'])->name('transfer-requests.reject');
    });

    Route::middleware('can:receive-transfer')->group(function () {
        Route::post('transfer-requests/{id}/receive', [TransferRequestController::class, 'receive'])->name('transfer-requests.receive');
    });
});

// Route::middleware(['auth', 'can:view-reports'])->group(function () {
//     Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
// });

Route::middleware(['auth', 'can:manage-users'])->group(function () {
    Route::resource('users', UserController::class)->except('show');
});

// Route::middleware(['auth', 'can:manage-roles'])->group(function () {
//     Route::resource('roles', RoleController::class)->except('show');
// });

require __DIR__ . '/auth.php';
