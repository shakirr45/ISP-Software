<?php
$allmikrotik = $obj->getAllData('mikrotik_user'); ?>
<div class="col-xl-12">
    <div class="card h-100 p-0">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="text-lg fw-semibold mb-0">Mikrotik Connection</h6>
        </div>
        <div class="card-body p-24">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <?php foreach ($allmikrotik as $mikrotiks):  ?>
                    <div class="col-md-2 mb-2"> <span id="m<?php echo $mikrotiks['id']; ?>"><button class="btn btn-primary" type="button" disabled> <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Loading...</button> </span></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>


<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Unmatching Secret List</h5>
    </div>
    <div class="card-body">
        <table
            class="table bordered-table mb-0"
            id="secrettable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">SL.No</th>
                    <th scope="col">Name</th>
                    <th scope="col">Password</th>
                    <th scope="col">Profile</th>
                    <th scope="col">Service</th>
                    <th scope="col">Last Log out</th>
                    <th scope="col">Status</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody id="secret_body">
            </tbody>
        </table>
    </div>
</div>
<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Unmatching Customer List</h5>
    </div>
    <div class="card-body">
        <table
            class="table bordered-table mb-0"
            id="Customertable"
            data-page-length="10">
            <thead>
                <tr>
                    <th scope="col">SL.No</th>
                    <th scope="col">Name</th>
                    <th scope="col">IP</th>
                    <th scope="col">Password</th>
                    <th scope="col">Profile</th>
                    <th scope="col">Status</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody id="customer_secret_body">
            </tbody>
        </table>
    </div>
</div>


<?php $obj->start_script(); ?>
<script src="assets/libs/jquery-tabledit/jquery.tabledit.min.js"></script>

<script>
    let sectetShow = true;

    function mkConnectCheck(id, mkip) {
        $.ajax({
            type: "GET",
            url: "./pages/mikrotik/connect_ajax.php",
            data: {
                'mkid': id
            },
            dataType: "JSON",
            success: function(response) {
                if (response.connection) {
                    $('#m' + id).html(`<button class="btn btn-success" onclick="mkAllSecret(${id})"   type="button">${mkip} ${response.status}</button>`);
                    if (sectetShow) {
                        mkAllSecret(`${id}`);
                    }
                    sectetShow = false;
                } else {
                    $('#m' + id).html(`<button class="btn btn-danger" disabled type="button">${mkip} ${response.status}</button>`);
                }

            },
            error: function(response) {
                $('#m' + id).html(`<button class="btn btn-danger" type="button"> ${mkip} ${response.status}</button>`);
            }
        });
    }

    function mkAllSecret(id) {
        $.ajax({
            type: "GET",
            url: "./pages/mikrotik/connect_ajax.php",
            data: {
                'mkidsecretunmatching': id
            },
            dataType: "JSON",
            success: function(response) {
                if (response.connection) {
                    $('#secret_body').html('');
                    $('#secret_body').html(`${response.status}`);
                    $('#customer_secret_body').html('');
                    $('#customer_secret_body').html(`${response.unmatching}`);
                } else {
                    $('#secret_body').html('');
                    $('#customer_secret_body').html('');
                }
            },
            error: function(e) {
                alert(e);
            }
        });
    }
</script>
<?php $i = 1;
foreach ($allmikrotik as $mikrotiks) { ?>
    <script>
        mkConnectCheck(`<?php echo $mikrotiks['id']; ?>`, `<?php echo $mikrotiks['mik_ip']; ?>`);
    </script>
<?php } ?>
<script>
    $(document).ready(function() {
        // Attach a click event listener to buttons with the class `secretAddSoft`
        $('#secrettable').on('click', '.secretAddSoft', function() {
            // Extract data attributes from the clicked button
            const id = $(this).data('mkid'); // Secret ID
            const status = $(this).data('status');
            const profile = $(this).data('profile');
            const password = $(this).data('password');
            const name = $(this).data('name');

            // AJAX POST request
            $.ajax({
                url: './pages/mikrotik/secretAddSoft.php', // Replace with your endpoint URL
                type: 'POST',
                data: {
                    mkid: id,
                    status: status,
                    profile: profile,
                    password: password,
                    name: name
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.status,
                            showConfirmButton: false,
                            timer: 2000,
                            position: 'top-end'
                        }).then(() => {
                            // Reload the page after user closes the alert
                            location.reload();
                        });

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.status,
                            showConfirmButton: false,
                            timer: 2000,
                            position: 'top-end',
                        }).then(() => {
                            // Reload the page after user closes the alert
                            location.reload();
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: 'An error occurred while processing your request.'
                    });
                }
            });
        });
        $("#Customertable").on('click', '.secretDelete', function() {
            var id = $(this).data('id');
            $.ajax({
                type: "GET",
                url: "./pages/mikrotik/delete_unmatching_secret.php",
                data: {
                    'id': id
                },
                dataType: "JSON",
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.status,
                            showConfirmButton: false,
                            timer: 2000,
                            position: 'top-end'
                        }).then(() => {
                            // Reload the page after user closes the alert
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.status,
                            showConfirmButton: false,
                            timer: 2000,
                            position: 'top-end',
                        }).then(() => {
                            // Reload the page after user closes the alert
                            location.reload();
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: 'An error occurred while processing your request.'
                    });
                }
            });
        });
    });
</script>
<?php $obj->end_script(); ?>