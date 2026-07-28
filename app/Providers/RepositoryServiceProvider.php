<?php

namespace App\Providers;


use App\Repositories\Eloquents\DepartmentRepository;
use App\Repositories\Eloquents\ItemLocationRepository;
use App\Repositories\Eloquents\ItemRepository;
use App\Repositories\Eloquents\StockLedgerRepository;
use App\Repositories\Eloquents\TransactionRepository;
use App\Repositories\Eloquents\TransferRequestRepository;
use App\Repositories\Eloquents\WarehouseRepository;
use App\Repositories\Interfaces\DepartmentRepositoryInterface;
use App\Repositories\Interfaces\ItemLocationRepositoryInterface;
use App\Repositories\Interfaces\ItemRepositoryInterface;
use App\Repositories\Interfaces\StockLedgerRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\TransferRequestRepositoryInterface;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;
use App\Services\DepartmentService;
use App\Services\Interfaces\DepartmentServiceInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\ItemServiceInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use App\Services\Interfaces\TransactionServiceInterface;
use App\Services\Interfaces\TransferRequestServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;
use App\Services\ItemLocationService;
use App\Services\ItemService;
use App\Services\StockLedgerService;
use App\Services\TransactionService;
use App\Services\TransferRequestService;
use App\Services\WarehouseService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Binding interface → implementasi.
     *
     * $bindings adalah konvensi bawaan Laravel — isinya otomatis
     * didaftarkan ke service container, jadi tidak perlu method
     * register() sama sekali. Wajib public.
     *
     * Kalau suatu saat implementasinya diganti (misal pindah ke
     * repository berbasis cache atau API eksternal), cukup ubah
     * di sini — Controller dan Service tidak perlu disentuh.
     */
    public array $bindings = [
        // Repository
        DepartmentRepositoryInterface::class      => DepartmentRepository::class,
        WarehouseRepositoryInterface::class       => WarehouseRepository::class,
        ItemRepositoryInterface::class            => ItemRepository::class,
        ItemLocationRepositoryInterface::class    => ItemLocationRepository::class,
        TransactionRepositoryInterface::class     => TransactionRepository::class,
        TransferRequestRepositoryInterface::class => TransferRequestRepository::class,
        StockLedgerRepositoryInterface::class     => StockLedgerRepository::class,

        // Service
        DepartmentServiceInterface::class      => DepartmentService::class,
        WarehouseServiceInterface::class       => WarehouseService::class,
        ItemServiceInterface::class            => ItemService::class,
        ItemLocationServiceInterface::class    => ItemLocationService::class,
        TransactionServiceInterface::class     => TransactionService::class,
        TransferRequestServiceInterface::class => TransferRequestService::class,
        StockLedgerServiceInterface::class     => StockLedgerService::class,
    ];
}
