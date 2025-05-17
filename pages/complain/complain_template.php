<?php

date_default_timezone_set('Asia/Dhaka');
$date_time = date('Y-m-d g:i:sA');
//$date        = date('Y-m-d');
$ip_add = $_SERVER['REMOTE_ADDR'];
$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

// $templates = $obj->view_all("tbl_complain_templates");
// print_r($templates);
if (isset($_POST['submit'])) {

    $form_template_data = array(
        'template' => $_POST['details']
    );

    $obj->Insert_data('tbl_complain_templates', $form_template_data);

    $obj->notificationStore('Complain Tempalte Added.', 'success');
    echo '<script> window.location="?page=complain_template"; </script>';
}

if (isset($_GET['dltoken'])) {
    $obj->Delete_data('tbl_complain_templates', 'id=' . $_GET['dltoken']);
    $obj->notificationStore('Complain Tempalte Deleted.', 'success');
    echo '<script> window.location="?q=complain_template"; </script>';
}
?>
<!-- Complain Template Modal -->
<div class="modal fade" id="complain-template-modal" tabindex="-1" aria-labelledby="complainTemplateLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="complainTemplateLabel">Add Complain Template</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-12">
                                <input type="hidden" name="template_id" id="template_id">
                                <div class="mb-3">
                                    <label for="details" class="form-label">Complain Template Details*</label>
                                    <textarea class="form-control" name="details" id="details" rows="3" placeholder="Enter complain details" data-error-message="Please provide a complain template." required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submit" class="btn btn-success waves-effect waves-light">Add Complain Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>




<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Complain Template List</h5>
        <button type="button" class="btn btn-success-600 radius-8 px-20 py-11" data-bs-toggle="modal" data-bs-target="#complain-template-modal">Create Package</button>

    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0"
            id="complain-template-datatable">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Template</th>
                    <th>Total Complaint</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody> </tbody>
        </table>
    </div>

</div>


<!-- delete modal content -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title fs-5" id="myCenterModalLabel">Are you sure you want to delete this complaint template?
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input type="hidden" id="deleteComplainId">
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="deleteComplain" class="btn btn-danger">Yes Delete</button>
            </div>
        </div>
    </div>
</div> <!-- end delete modal -->

<!-- <script>
    $('select[name="customer_id"]').select2({
        placeholder: "Select",
        allowClear: true,
    });
</script> -->


<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        var table = $('#complain-template-datatable').DataTable({
            // dom: 'Bflrtip',
            responsive: true,
            processing: true,
            stateSave: true,
            pagingType: "full_numbers",
            lengthChange: true,
            keys: true,
            ajax: {
                url: "./pages/complain/getComplainTemplatesAjax.php", // URL to your PHP file
                type: "GET",
                data: function(d) {
                    // console.log('complain-template', d);
                }
            },
            columns: [{
                    data: 'sl'
                },
                {
                    data: 'template',
                    render: function(data, type, row) {
                        return data.template
                    }
                },
                {
                    data: 'template',
                    render: function(data, type, row) {
                        return `<a href="?page=individual_complain_details&complain_id=` + row.id + `""  class='btn btn-success waves-effect waves-light btn-sm'>${data.totalComplaints}</a>`
                    }
                },
                {
                    data: 'id',
                    render: function(data, type, row) {
                        return `
                        <a href="?page=edit_complain_template&token=` + data + `"  class='btn btn-warning waves-effect waves-light btn-sm'>Edit</a>
                        <a data-bs-toggle="modal" data-bs-target="#deleteModal" data-delete-id="${data}"  class='btn btn-danger waves-effect waves-light btn-sm delete-modal'>Delete</a>`;
                    }
                }
            ],
        });

        // delete complain
        $('#deleteComplain').on('click', function() {
            const deleteComplainId = $('#deleteComplainId').val();

            // AJAX request
            $.ajax({
                url: './pages/complain/delete_complain_template.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    complain_id: deleteComplainId
                }),
                dataType: 'json',
                success: function(response) {
                    $('#deleteModal').modal('hide');
                    table.ajax.reload();
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON ? xhr.responseJSON.message : 'An unexpected error occurred');
                }
            });
        });


        $(document).on('click', '.delete-modal', function() {
            // Get the ID from the clicked button
            const complainDeleteId = $(this).data('delete-id');
            $('#deleteComplainId').val(complainDeleteId)
        });
    });
</script>

<?php $obj->end_script(); ?>