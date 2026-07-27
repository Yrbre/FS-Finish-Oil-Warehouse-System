<script src="{{ asset('design/dark/js/jquery.min.js') }}"></script>
<script src="{{ asset('design/dark/js/popper.min.js') }}"></script>
<script src="{{ asset('design/dark/js/moment.min.js') }}"></script>
<script src="{{ asset('design/dark/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('design/dark/js/simplebar.min.js') }}"></script>
<script src='{{ asset('design/dark/js/daterangepicker.js') }}'></script>
<script src='{{ asset('design/dark/js/jquery.stickOnScroll.js') }}'></script>
<script src="{{ asset('design/dark/js/tinycolor-min.js') }}"></script>
<script src="{{ asset('design/dark/js/config.js') }}"></script>
<script src="{{ asset('design/dark/js/d3.min.js') }}"></script>
<script src="{{ asset('design/dark/js/topojson.min.js') }}"></script>
<script src="{{ asset('design/dark/js/datamaps.all.min.js') }}"></script>
<script src="{{ asset('design/dark/js/datamaps-zoomto.js') }}"></script>
<script src="{{ asset('design/dark/js/datamaps.custom.js') }}"></script>
<script src="{{ asset('design/dark/js/Chart.min.js') }}"></script>
<script src="{{ asset('design/dark/js/gauge.min.js') }}"></script>
<script src="{{ asset('design/dark/js/jquery.sparkline.min.js') }}"></script>
<script src="{{ asset('design/dark/js/apexcharts.min.js') }}"></script>
<script src="{{ asset('design/dark/js/apexcharts.custom.js') }}"></script>
<script src='{{ asset('design/dark/js/jquery.mask.min.js') }}'></script>
<script src='{{ asset('design/dark/js/select2.min.js') }}'></script>
<script src='{{ asset('design/dark/js/jquery.steps.min.js') }}'></script>
<script src='{{ asset('design/dark/js/jquery.validate.min.js') }}'></script>
<script src='{{ asset('design/dark/js/jquery.timepicker.js') }}'></script>
<script src='{{ asset('design/dark/js/dropzone.min.js') }}'></script>
<script src='{{ asset('design/dark/js/uppy.min.js') }}'></script>
<script src='{{ asset('design/dark/js/quill.min.js') }}'></script>
<script src='{{ asset('design/dark/js/jquery.dataTables.min.js') }}'></script>
<script src='{{ asset('design/dark/js/dataTables.bootstrap4.min.js') }}'></script>
<script src='{{ asset('design/dark/js/dataTables.responsive.min.js') }}'></script>
<script src='{{ asset('design/dark/js/responsive.bootstrap4.min.js') }}'></script>
<script src="{{ asset('design/dark/js/apps.js') }}"></script>
<script src="{{ asset('design/dark/sweetalert2/dist/sweetalert2.min.js') }}"></script>

<script>
    (function($) {
        function refreshSelect2Width() {
            $('.select2, .select2-multi').each(function() {
                const instance = $(this).data('select2');

                if (instance) {
                    instance.$container.css('width', '100%');
                }
            });
        }

        $(document).ready(function() {
            refreshSelect2Width();
        });

        $(document).on('click', '.collapseSidebar', function() {
            setTimeout(refreshSelect2Width, 320);
        });

        $(window).on('resize', refreshSelect2Width);
    })(jQuery);
</script>

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
