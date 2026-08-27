<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="{{ asset('design/dark/assets/images/LogoTifico.png') }}">
    <title>Oil Management</title>
    <!--CSS -->
    @include('layouts.style')
    @stack('style')
</head>

<body class="horizontal dark  ">
    <div class="wrapper">
        @include('layouts.navbar')
        <main role="main" class="main-content">
            <div class="container-fluid">
                @yield('content')
            </div> <!-- .container-fluid -->
        </main> <!-- main -->
    </div> <!-- .wrapper -->
    @include('layouts.script')
    <script>
        (function() {
            const ICON = {
                min_stock: 'fe-alert-triangle text-warning',
                near_expiry: 'fe-clock text-danger',
            };

            function loadNotifications() {
                $.get('{{ route('notifications.latest') }}').done(function(res) {
                    const count = res.unread_count;

                    $('#notifDot').toggleClass('d-none', count === 0);
                    $('#notifCount').text(count).toggleClass('d-none', count === 0);

                    if (!res.items.length) {
                        $('#notifList').html(
                            '<p class="text-muted small text-center py-3 mb-0">Tidak ada notifikasi baru.</p>'
                            );
                        return;
                    }

                    let html = '';

                    res.items.forEach(function(n) {
                        const icon = ICON[n.type] ?? 'fe-info text-primary';

                        html += `
                            <a href="/notifications/${n.id}/read" class="dropdown-item px-3 py-2">
                                <div class="d-flex">
                                    <span class="fe ${icon} fe-16 mr-2 mt-1"></span>
                                    <div style="white-space: normal;">
                                        <div class="small font-weight-bold">${n.title}</div>
                                        <div class="small text-muted">${n.message}</div>
                                        <div class="small text-muted">${n.ago}</div>
                                    </div>
                                </div>
                            </a>`;
                    });

                    $('#notifList').html(html);
                });
            }

            $(document).ready(function() {
                loadNotifications();
                setInterval(loadNotifications, 60000);
            });
        })();
    </script>
    <script>
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                theme: 'dark',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false,
            });
        @endif
    </script>
    <script>
        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                theme: 'dark',
                text: '{{ session('error') }}',
                showConfirmButton: true,
            });
        @endif
    </script>
    @stack('scripts')
</body>

</html>
