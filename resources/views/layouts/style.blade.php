<link rel="stylesheet" href="{{ asset('design/dark/css/simplebar.css') }}">
<!-- Fonts CSS -->
<link
    href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100;0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap"
    rel="stylesheet">
<!-- Icons CSS -->
<link rel="stylesheet" href="{{ asset('design/dark/css/feather.css') }}">
<link rel="stylesheet" href="{{ asset('design/dark/css/dataTables.bootstrap4.css') }}">
<link rel="stylesheet" href="{{ asset('design/dark/css/responsive.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('design/dark/css/select2.css') }}">
<link rel="stylesheet" href="{{ asset('design/dark/css/dropzone.css') }}">
<link rel="stylesheet" href="{{ asset('design/dark/css/uppy.min.css') }}">
<link rel="stylesheet" href="{{ asset('design/dark/css/jquery.steps.css') }}">
<link rel="stylesheet" href="{{ asset('design/dark/css/jquery.timepicker.css') }}">
<link rel="stylesheet" href="{{ asset('design/dark/css/quill.snow.css') }}">
<!-- Date Range Picker CSS -->
<link rel="stylesheet" href="{{ asset('design/dark/css/daterangepicker.css') }}">
<!-- App CSS -->
<link rel="stylesheet" href="{{ asset('design/dark/css/app-light.css') }}" id="lightTheme" disabled>
<link rel="stylesheet" href="{{ asset('design/dark/css/app-dark.css') }}" id="darkTheme">
<link rel="stylesheet" href="{{ asset('design/dark/css/fa-all.min.css') }}">

<link rel="stylesheet" href="{{ asset('design/dark/sweetalert2/dist/sweetalert2.min.css') }}">
<script>
    document.addEventListener('input', function(e) {
        if (e.target.matches('.uppercase')) {
            let start = e.target.selectionStart;
            let end = e.target.selectionEnd;

            e.target.value = e.target.value.toUpperCase();

            e.target.setSelectionRange(start, end);
        }
    });
</script>
