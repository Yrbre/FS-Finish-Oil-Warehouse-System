<?php

// config/menu.php
// Menu di-generate otomatis di Blade dengan mencocokkan permission user.
// Tidak perlu tabel menu terpisah di database.

return [
    [
        'label' => 'Dashboard',
        'route' => 'dashboard',
        'icon' => 'ti ti-home',
        'permission' => null, // null = semua user login bisa lihat
    ],
    [
        'label' => 'Master Data',
        'icon' => 'ti ti-database',
        'permission' => null,
        'children' => [
            ['label' => 'Departments', 'route' => 'departments.index', 'permission' => 'manage-departments'],
            ['label' => 'Warehouses', 'route' => 'warehouses.index', 'permission' => 'manage-warehouses'],
            ['label' => 'Items', 'route' => 'items.index', 'permission' => 'manage-items'],
        ],
    ],
    [
        'label' => 'Item Locations',
        'route' => 'item-locations.index',
        'icon' => 'ti ti-map-pin',
        'permission' => 'manage-item-locations',
    ],
    [
        'label' => 'Transactions',
        'route' => 'transactions.index',
        'icon' => 'ti ti-arrows-exchange',
        'permission' => 'create-transaction',
    ],
    [
        'label' => 'Permintaan Kirim Barang ',
        'route' => 'transfer-requests.index',
        'icon' => 'ti ti-truck',
        'permission' => 'manage-transfer-request',
    ],
    [
        'label' => 'Reports',
        'route' => 'reports.index',
        'icon' => 'ti ti-chart-bar',
        'permission' => 'view-reports',
    ],
    [
        'label' => 'User Management',
        'icon' => 'ti ti-users',
        'permission' => null,
        'children' => [
            ['label' => 'Users', 'route' => 'users.index', 'permission' => 'manage-users'],
            ['label' => 'Roles', 'route' => 'roles.index', 'permission' => 'manage-roles'],
        ],
    ],
];
