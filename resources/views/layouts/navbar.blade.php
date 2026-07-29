<nav class="navbar navbar-expand-lg navbar-light bg-white flex-row border-bottom shadow">
    <div class="container-fluid">
        <a class="navbar-brand mx-lg-1 mr-0" href="{{ route('dashboard') }}">
            <svg version="1.1" id="logo" class="navbar-brand-img brand-sm" xmlns="http://www.w3.org/2000/svg"
                xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 120 120" xml:space="preserve">
                <g>
                    <polygon class="st0" points="78,105 15,105 24,87 87,87 	" />
                    <polygon class="st0" points="96,69 33,69 42,51 105,51 	" />
                    <polygon class="st0" points="78,33 15,33 24,15 87,15 	" />
                </g>
            </svg>
        </a>
        <button class="navbar-toggler mt-2 mr-auto toggle-sidebar text-muted">
            <i class="fe fe-menu navbar-toggler-icon"></i>
        </button>
        <div class="navbar-slide bg-white ml-lg-4 justify-content-start" id="navbarSupportedContent">
            <a href="#" class="btn toggle-sidebar d-lg-none text-muted ml-2 mt-3" data-toggle="toggle">
                <i class="fe fe-x"><span class="sr-only"></span></i>
            </a>
            <ul class="navbar-nav mr-auto">

                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                        class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                </li>

                {{-- Master Data — tampil kalau user punya salah satu permission master --}}
                @canany(['manage-departments', 'manage-items', 'manage-users', 'manage-warehouses'])
                    <li class="nav-item dropdown">
                        <a href="#" id="masterDropdown" class="dropdown-toggle nav-link" role="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="ml-lg-2">Master</span>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="masterDropdown">
                            @can('manage-departments')
                                <a class="nav-link pl-lg-2" href="{{ route('departments.index') }}"><span
                                        class="ml-1">Department</span></a>
                            @endcan
                            @can('manage-items')
                                <a class="nav-link pl-lg-2" href="{{ route('items.index') }}"><span class="ml-1">Item
                                        Oil</span></a>
                            @endcan
                            @can('manage-warehouses')
                                <a class="nav-link pl-lg-2" href="{{ route('warehouses.index') }}"><span
                                        class="ml-1">Warehouses</span></a>
                            @endcan
                            {{-- @can('manage-users')
                                <a class="nav-link pl-lg-2" href="{{ route('users.index') }}"><span
                                        class="ml-1">Users</span></a>
                            @endcan --}}
                        </div>
                    </li>
                @endcanany

                {{-- Transaksi --}}
                @canany(['create-transaction', 'manage-transfer-request', 'approve-transfer'])
                    <li class="nav-item dropdown">
                        <a class="dropdown-toggle nav-link pl-lg-3" href="#" id="transaksiDropdown" role="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Transaksi
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="transaksiDropdown">
                            @can('create-transaction')
                                <li>
                                    <a class="nav-link pl-lg-2" href="{{ route('transactions.index') }}"><span
                                            class="ml-1">Supply Oil</span></a>
                                </li>
                            @endcan
                            @canany(['manage-transfer-request', 'approve-transfer'])
                                <li>
                                    <a class="nav-link pl-lg-2" href="{{ route('transfer-requests.index') }}"><span
                                            class="ml-1">Transfer Request</span></a>
                                </li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany

                {{-- Inventory --}}
                @can('manage-item-locations')
                    <li class="nav-item dropdown">
                        <a href="#" id="inventoryDropdown" class="dropdown-toggle nav-link" role="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="ml-lg-2">Inventory</span>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="inventoryDropdown">
                            <a class="nav-link pl-lg-2" href="{{ route('item-locations.index') }}"><span class="ml-1">Item
                                    Onhand</span></a>
                        </div>
                    </li>
                @endcan

                {{-- Laporan --}}
                {{-- @can('view-reports')
                    <li class="nav-item">
                        <a href="{{ route('reports.index') }}"
                            class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">Laporan</a>
                    </li>
                @endcan --}}

            </ul>
        </div>

        <ul class="navbar-nav d-flex flex-row">
            <li class="nav-item dropdown ml-lg-0">
                <a class="nav-link dropdown-toggle text-muted" href="#" id="navbarDropdownMenuLink" role="button"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="avatar avatar-sm mt-2">
                        <img src="{{ asset('design/dark/assets/avatars/face-1.jpg') }}" alt="..."
                            class="avatar-img rounded-circle">
                        <span class="ml-2">{{ auth()->user()->name }}</span>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                    <li class="nav-item">
                        <span class="dropdown-item-text small text-muted">
                            {{ strtoupper(auth()->user()->getRoleNames()->first() ?? '-') }}
                        </span>
                    </li>
                    <div class="dropdown-divider"></div>
                    <li class="nav-item">
                        <a class="nav-link pl-3" href="{{ route('profile.edit') }}">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link pl-3" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            Logout
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST"
                            style="display: none;">
                            @csrf
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
