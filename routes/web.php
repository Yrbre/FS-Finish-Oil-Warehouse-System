<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemLocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransferRequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Department
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::middleware('can:departments.view')->group(function () {
        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
    });
    Route::middleware('can:departments.create')->group(function () {
        Route::get('departments/create', [DepartmentController::class, 'create'])->name('departments.create');
        Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
    });
    Route::middleware('can:departments.update')->group(function () {
        Route::get('departments/{id}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::put('departments/{id}', [DepartmentController::class, 'update'])->name('departments.update');
    });
    Route::middleware('can:departments.delete')->group(function () {
        Route::delete('departments/{id}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Warehouse
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::middleware('can:warehouses.view')->group(function () {
        Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    });
    Route::middleware('can:warehouses.create')->group(function () {
        Route::get('warehouses/create', [WarehouseController::class, 'create'])->name('warehouses.create');
        Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
    });
    Route::middleware('can:warehouses.update')->group(function () {
        Route::get('warehouses/{id}/edit', [WarehouseController::class, 'edit'])->name('warehouses.edit');
        Route::put('warehouses/{id}', [WarehouseController::class, 'update'])->name('warehouses.update');
    });
    Route::middleware('can:warehouses.delete')->group(function () {
        Route::delete('warehouses/{id}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Item Master
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::middleware('can:items.view')->group(function () {
        Route::get('items', [ItemController::class, 'index'])->name('items.index');
        Route::get('items/{id}/detail', [ItemController::class, 'detail'])->name('items.detail');
        Route::get('items/get-stock', [ItemController::class, 'getStock'])->name('items.get-stock');
    });
    Route::middleware('can:items.create')->group(function () {
        Route::get('items/create', [ItemController::class, 'create'])->name('items.create');
        Route::post('items', [ItemController::class, 'store'])->name('items.store');
    });
    Route::middleware('can:items.update')->group(function () {
        Route::get('items/{id}/edit', [ItemController::class, 'edit'])->name('items.edit');
        Route::put('items/{id}', [ItemController::class, 'update'])->name('items.update');
    });
    Route::middleware('can:items.delete')->group(function () {
        Route::delete('items/{id}', [ItemController::class, 'destroy'])->name('items.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Item Locations (Stok Gudang)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::middleware('can:item-locations.view')->group(function () {
        Route::get('item-locations', [ItemLocationController::class, 'index'])->name('item-locations.index');
    });
    Route::middleware('can:item-locations.create')->group(function () {
        Route::get('item-locations/create', [ItemLocationController::class, 'create'])->name('item-locations.create');
        Route::post('item-locations', [ItemLocationController::class, 'store'])->name('item-locations.store');
    });
    Route::middleware('can:item-locations.update')->group(function () {
        Route::get('item-locations/{id}/edit', [ItemLocationController::class, 'edit'])->name('item-locations.edit');
        Route::put('item-locations/{id}', [ItemLocationController::class, 'update'])->name('item-locations.update');
    });
    Route::middleware('can:item-locations.delete')->group(function () {
        Route::delete('item-locations/{id}', [ItemLocationController::class, 'destroy'])->name('item-locations.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Transaksi (PORC / CONS / ADJ) — permission dipecah per jenis
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Index & AJAX helper: cukup auth, controller yang cek detail
    // (canAny salah satu dari 3 permission view) dan filter jenis
    // yang boleh dilihat.
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/get-stock', [TransactionController::class, 'getStock'])->name('transactions.get-stock');
    Route::get('transactions/get-lots', [TransactionController::class, 'getLots'])->name('transactions.get-lots');

    Route::middleware('can:transactions.porc.create')->group(function () {
        Route::get('transactions/supply-oil', [TransactionController::class, 'createPorc'])->name('transactions.porc.create');
        Route::post('transactions/supply-oil', [TransactionController::class, 'storePorc'])->name('transactions.porc.store');
    });

    Route::middleware('can:transactions.cons.create')->group(function () {
        Route::get('transactions/pemakaian', [TransactionController::class, 'createCons'])->name('transactions.cons.create');
        Route::post('transactions/pemakaian', [TransactionController::class, 'storeCons'])->name('transactions.cons.store');
    });

    Route::middleware('can:transactions.adj.create')->group(function () {
        Route::get('transactions/adjustment', [TransactionController::class, 'createAdj'])->name('transactions.adj.create');
        Route::post('transactions/adjustment', [TransactionController::class, 'storeAdj'])->name('transactions.adj.store');
    });

    Route::middleware('can:transactions.porc.update')->group(function () {
        Route::get('transactions/{id}/edit', [TransactionController::class, 'editPorc'])->name('transactions.porc.edit');
        Route::put('transactions/{id}', [TransactionController::class, 'updatePorc'])->name('transactions.porc.update');
    });

    Route::middleware('can:transactions.porc.delete')->group(function () {
        Route::delete('transactions/{id}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Transfer Request / Permintaan Kirim Barang
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Route statis (create) HARUS didaftarkan SEBELUM route dinamis {id},
    // supaya /transfer-requests/create tidak salah tertangkap sebagai
    // /transfer-requests/{id} dengan id="create".
    Route::middleware('can:transfer-requests.create')->group(function () {
        Route::get('transfer-requests/create', [TransferRequestController::class, 'create'])->name('transfer-requests.create');
        Route::get('transfer-requests/package-sizes', [TransferRequestController::class, 'getPackageSizes'])->name('transfer-requests.package-sizes');
        Route::post('transfer-requests', [TransferRequestController::class, 'store'])->name('transfer-requests.store');
    });

    Route::post('transfer-requests/cetak-batch', [TransferRequestController::class, 'cetakBatch'])
        ->name('transfer-requests.cetak-batch');

    Route::middleware('can:transfer-requests.view')->group(function () {
        Route::get('transfer-requests', [TransferRequestController::class, 'index'])->name('transfer-requests.index');
        Route::get('transfer-requests/{id}', [TransferRequestController::class, 'show'])
            ->whereNumber('id')->name('transfer-requests.show');
    });

    Route::middleware('can:transfer-requests.cancel')->group(function () {
        Route::post('transfer-requests/{id}/cancel', [TransferRequestController::class, 'cancel'])->name('transfer-requests.cancel');
    });
    Route::middleware('can:transfer-requests.approve')->group(function () {
        Route::post('transfer-requests/{id}/approve', [TransferRequestController::class, 'approve'])->name('transfer-requests.approve');
    });
    Route::middleware('can:transfer-requests.reject')->group(function () {
        Route::post('transfer-requests/{id}/reject', [TransferRequestController::class, 'reject'])->name('transfer-requests.reject');
    });
    Route::middleware('can:transfer-requests.receive')->group(function () {
        Route::post('transfer-requests/{id}/receive', [TransferRequestController::class, 'receive'])->name('transfer-requests.receive');
    });

    // Tanda terima barang — akses diatur per user lewat kolom
    // users.can_issue_receipt, dicek di service.
    Route::post('transfer-requests/{id}/receipt', [TransferRequestController::class, 'issueReceipt'])->name('transfer-requests.issue-receipt');
    Route::get('transfer-requests/{id}/receipt', [TransferRequestController::class, 'printReceipt'])->name('transfer-requests.receipt');
});

/*
|--------------------------------------------------------------------------
| User Management
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::middleware('can:users.view')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
    });
    Route::middleware('can:users.create')->group(function () {
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
    });
    Route::middleware('can:users.update')->group(function () {
        Route::get('users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{id}', [UserController::class, 'update'])->name('users.update');
    });
    Route::middleware('can:users.delete')->group(function () {
        Route::delete('users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Role & Permission
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::middleware('can:roles.view')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    });
    Route::middleware('can:roles.create')->group(function () {
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    });
    Route::middleware('can:roles.update')->group(function () {
        Route::get('roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{id}', [RoleController::class, 'update'])->name('roles.update');
    });
    Route::middleware('can:roles.delete')->group(function () {
        Route::delete('roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Laporan
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'can:reports.view'])->group(function () {
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
});

require __DIR__ . '/auth.php';
