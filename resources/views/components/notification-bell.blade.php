<li class="nav-item dropdown mr-3">
    <a class="nav-link text-muted my-2" href="#" id="notificationBell" role="button" data-toggle="dropdown"
        aria-haspopup="true" aria-expanded="false">
        <span class="fe fe-bell fe-16"></span>
        <span class="badge badge-danger badge-pill position-absolute d-none" id="notifDot"
            style="top: 8px; right: 2px; padding: 3px 5px; font-size: 9px;">&nbsp;</span>
    </a>
    <div class="dropdown-menu dropdown-menu-right dropdown-menu-big" aria-labelledby="notificationBell"
        style="min-width: 320px;">
        <div class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2">
            <strong>Notifikasi</strong>
            <span class="badge badge-danger d-none" id="notifCount">0</span>
        </div>
        <div class="dropdown-divider"></div>

        <div id="notifList">
            <p class="text-muted small text-center py-3 mb-0">Memuat...</p>
        </div>

        <div class="dropdown-divider"></div>
        <a href="{{ route('notifications.index') }}" class="dropdown-item text-center small">
            Lihat semua notifikasi
        </a>
    </div>
</li>
