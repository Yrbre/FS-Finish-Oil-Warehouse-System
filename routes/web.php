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
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
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

Route::middleware('auth')->group(function () {

    Route::middleware('can:manage-departments')->group(function () {
        Route::resource('departments', DepartmentController::class)->except('show');
    });

    Route::middleware('can:manage-warehouses')->group(function () {
        Route::resource('warehouses', WarehouseController::class)->except('show');
    });

    Route::middleware('can:manage-items')->group(function () {
        // Route spesifik didaftarkan sebelum resource supaya tidak
        // ketabrak pola items/{item}
        Route::get('items/get-stock', [ItemController::class, 'getStock'])->name('items.get-stock');
        Route::get('items/{id}/detail', [ItemController::class, 'detail'])->name('items.detail');

        Route::resource('items', ItemController::class)->except('show');
    });
});

require __DIR__ . '/auth.php';
