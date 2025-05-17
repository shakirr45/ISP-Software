<?php
include('employee.php');
$token = isset($_GET['delete-token']) ? $_GET['delete-token'] : NULL;

$obj->deleteData("tbl_employee", ['where' => ['id', '=', $token]]);
?>
<div class="card basic-data-table">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h5 class="card-title mb-0">Employee</h5>
    <button type="button" class="btn btn-success waves-effect waves-light col-md-2 d-flex align-items-center gap-3" data-bs-toggle="modal" data-bs-target="#con-close-modal"><iconify-icon icon="mdi:plus"></iconify-icon> Add New Employee</button>
    </div>
    <div class="card-body table-responsive">
        <table
            class="table bordered-table mb-0 datatable"
            id="dataTable"
            data-page-length="10">
            <thead>
                <tr>
                    <th>SL</th>
                    <th>Employee ID</th>
                    <th>Employee Name</th>
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>National ID</th>
                    <th>Designation</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $viewEmployee = $obj->getAllData("tbl_employee");
                $i = 1;
                foreach ($viewEmployee as $value) : ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo isset($value['employee_id']) ? $value['employee_id'] : NULL;  ?></td>
                        <td><?php echo isset($value['employee_name']) ? $value['employee_name'] : 'N/A';  ?></td>
                        <td><?php echo isset($value['employee_mobile_no']) ? $value['employee_mobile_no'] : 'N/A';  ?></td>
                        <td><?php echo isset($value['employee_email']) ? $value['employee_email'] : 'N/A';  ?></td>
                        <td><?php echo isset($value['employee_national_id']) ? $value['employee_national_id'] : 'N/A';  ?></td>
                        <td><?php echo isset($value['designation']) ? $value['designation'] : 'N/A';  ?></td>
                        <td><?php echo isset($value['employee_address']) ? $value['employee_address'] : 'N/A';  ?></td>
                        <td>
                            <?php if ($value['employee_status'] == 1) { ?>
                                <span class="text-success">Active</span>
                            <?php } else { ?>
                                <span class="text-danger">Inactive</span>
                            <?php } ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group">

                                <!-- <button type="button" class="btn btn-warning waves-effect waves-light employee_update_data"data-emp_id="<?php echo @$value['id'] ?>"data-name="<?php echo @$value['employee_name'] ?>"data-phone="<?php echo @$value['employee_mobile_no'] ?>" 
                                 data-email="<?php echo @$value['employee_email'] ?>" 
                                 data-nid="<?php echo @$value['employee_national_id'] ?>"
                                 data-designation="<?php echo @$value['designation'] ?>"
                                 data-address="<?php echo @$value['employee_address'] ?>"
                                 data-joining_date="<?php echo @$value['joining_date'] ?>"
                                 
                                 data-bs-toggle="modal" 
                                 data-bs-target="#con-close-modal">
                                 
                                 <span  class="fas fa-edit"></span></button> -->
                                <a
                                    href="?page=employee_edit&token=<?php echo isset($value['id']) ? $value['id'] : NULL; ?>"
                                    class="btn btn-warning waves-effect waves-light btn-sm">
                                    Edit <span class="glyphicon glyphicon-remove"></span>
                                </a>

                                <button
                                    type="button"
                                    data-delete-url="?page=employee_view&delete-token=<?php echo isset($value['id']) ? $value['id'] : NULL; ?>"
                                    class="btn btn-danger waves-effect waves-light btn-sm delete-confirm">
                                    Delete <span class="glyphicon glyphicon-remove"></span>
                                </button>


                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </div>
            </div>
        </div>
    </div>
</div>





<!--zone  modal content -->
<div id="con-close-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title fs-5">Employee</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" id="addUser" enctype="multipart/form-data">

                <div class="row">


                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <!-- <input type="hidden" name="em_id" id="em_id"> -->
                                    <div class="col-md-12">

                                        <div class="mb-3">
                                            <label for="name" class="form-label">Employee's Name</label>
                                            <input type="text" id="name" name="name" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Employee's Mobile No</label>
                                            <input type="text" id="phone" name="phone" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Employee's Email</label>
                                            <input type="email" id="email" name="email" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nid" class="form-label">Employee's National Id</label>
                                            <input type="text" id="nid" name="nid" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="designation" class="form-label">Employee's Designation</label>
                                            <input type="text" id="designation" name="designation" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="joining_date" class="form-label">Employee's Join Date</label>
                                            <input type="date" id="joining_date" name="joining_date" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="activeStatus" class="form-label">Active Status</label>
                                            <select id="activeStatus" name="activeStatus" class="form-control">
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Employee's Address</label>
                                            <textarea id="address" name="address" class="form-control"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <br>
                                        <br>

                                    </div>
                                </div> <!-- end row -->
                            </div>
                        </div> <!-- end card-->
                    </div> <!-- end col-->
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 text-center">
                                        <button type="submit" class="btn btn-success waves-effect waves-light btn-lg" name="submit">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end card-->
                    </div> <!-- end col-->
                </div>

            </form>
        </div>
    </div>
</div><!-- /.modal -->
<?php $obj->start_script(); ?>
<script>
     $(document).ready(function() {
        $('#dataTable').DataTable({
            "responsive": true,
            "paging": true,
            "searching": true,
            "info": true,
            dom: `<"row"<"col-sm-6"l><"col-sm-6 text-end"f>>` +
            `<"row"<"col-sm-12 text-end"B>>` +
            `<"row dt-layout-row"<"col-sm-12"tr>>` +
            `<"row"<"col-sm-5"i><"col-sm-7 text-end"p>>`,
        buttons: [
            { extend: 'copy', className: 'btn btn-primary btn-sm' },
            { extend: 'excel', className: 'btn btn-success btn-sm' },
            { extend: 'pdf', className: 'btn btn-danger btn-sm' },
            { extend: 'print', className: 'btn btn-info btn-sm' }
        ],
        responsive: true,
        processing: true,
        serverSide: false, // Set this to true if using server-side processing
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true
        });
    });
    var elements = document.getElementsByClassName('employee_update_data');
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', function() {
            var id = this.getAttribute('data-emp_id');
            var name = this.getAttribute('data-name');
            var phone = this.getAttribute('data-phone');
            var email = this.getAttribute('data-email');
            var nid = this.getAttribute('data-nid');
            var address = this.getAttribute('data-address');
            var designation = this.getAttribute('data-designation');

            var joining_date = this.getAttribute('data-joining_date');

            document.getElementById('em_id').value = id;
            document.getElementById('name').value = name;
            document.getElementById('phone').value = phone;
            document.getElementById('email').value = email;
            document.getElementById('nid').value = nid;
            document.getElementById('address').value = address;
            document.getElementById('joining_date').value = joining_date;
            document.getElementById('designation').value = designation;


        });
    }

    // Use event delegation for dynamically created elements
    $(document).on('click', '.delete-confirm', function(e) {
        e.preventDefault(); // Prevent default behavior

        // Get the delete URL from the button's data attribute
        const deleteUrl = $(this).data('delete-url');

        // Display SweetAlert confirmation
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to the delete URL
                window.location.href = deleteUrl;
            }
        });
    });
</script>
<?php $obj->end_script(); ?>