<!-- jQuery library js -->
<script src="assets/js/lib/jquery-3.7.1.min.js"></script>
<!-- Bootstrap js -->
<script src="assets/js/lib/bootstrap.bundle.min.js"></script>
<!-- Apex Chart js -->
<script src="assets/js/lib/apexcharts.min.js"></script>
<!-- Data Table js -->
<script src="assets/js/lib/dataTables.min.js"></script>
<!-- Iconify Font js -->
<script src="assets/js/lib/iconify-icon.min.js"></script>
<!-- jQuery UI js -->
<script src="assets/js/lib/jquery-ui.min.js"></script>
<!-- Vector Map js -->
<script src="assets/js/lib/jquery-jvectormap-2.0.5.min.js"></script>
<script src="assets/js/lib/jquery-jvectormap-world-mill-en.js"></script>
<!-- Popup js -->
<script src="assets/js/lib/magnifc-popup.min.js"></script>
<!-- Slick Slider js -->
<script src="assets/js/lib/slick.min.js"></script>
<!-- prism js -->
<script src="assets/js/lib/prism.js"></script>
<!-- file upload js -->
<script src="assets/js/lib/file-upload.js"></script>
<!-- audioplayer -->
<script src="assets/js/lib/audioplayer.js"></script>

<!-- main js -->
<script src="assets/js/app.js"></script>

<script src="assets/js/homeFiveChart.js"></script>

<!-- Sweet Alerts js -->
<script src="assets/js/lib/sweetalert2/sweetalert2.all.min.js"></script>
<!-- Sweet alert init js-->
<script src="assets/js/lib/sweet-alerts.init.js"></script>

<!-- DataTables Buttons JS -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<!-- JSZip for Export to Excel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<!-- PDFMake for Export to PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.68/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.68/vfs_fonts.js"></script>
<!-- Buttons HTML5 Export -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<!-- Buttons Print -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<!-- Dark mode js -->
<!-- <script src="assets/js/layout.js"></script> -->

<!-- <script src="assets/libs/mohithg-switchery/switchery.min.js"></script> -->
<!-- Materialize JS -->
<!-- Materialize JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script src="https://d3js.org/d3.v7.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- third party js ends -->
<script>
    $('#alertmessagebtn').on('click', function() {
        $('#alertmessage').remove();
    });

    function numbersOnly(e) // Numeric Validation
    {
        var unicode = e.charCode ? e.charCode : e.keyCode
        if (unicode != 8) {
            if ((unicode < 2534 || unicode > 2543) && (unicode < 48 || unicode > 57)) {
                return false;
            }
        }
    }
</script>
<script>
    (() => {
        'use strict';

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        const forms = document.querySelectorAll('.needs-validation');

        // Loop over them and prevent submission
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                // Check validity of the form
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();

                    // Find all invalid fields and display error messages
                    const invalidFields = form.querySelectorAll(':invalid');
                    invalidFields.forEach(field => {
                        let errorMessage = field.getAttribute('data-error-message') || 'This field is required.';
                        if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('error-message')) {
                            const errorElement = document.createElement('div');
                            errorElement.classList.add('error-message', 'text-danger', 'mt-1');
                            errorElement.textContent = errorMessage;
                            field.parentElement.appendChild(errorElement);
                        }
                    });
                } else {
                    // Remove error messages on successful validation
                    const errorMessages = form.querySelectorAll('.error-message');
                    errorMessages.forEach(error => error.remove());
                }

                form.classList.add('was-validated');
            }, false);

            // Remove error messages on input change
            form.querySelectorAll('input, select').forEach(input => {
                input.addEventListener('input', () => {
                    if (input.nextElementSibling && input.nextElementSibling.classList.contains('error-message')) {
                        input.nextElementSibling.remove();
                    }
                });
            });
        });
    })();
</script>