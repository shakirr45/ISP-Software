<?php
include('employee.php');
$token = isset($_GET['delete-token']) ? $_GET['delete-token'] : NULL;

$obj->deleteData("tbl_employee", ['where' => ['id', '=', $token]]);
?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Employee</h4>
                <p class="text-muted font-13 mb-4">Employee</p>

                <button type="button" class="btn btn-info waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#con-close-modal"><i class="mdi mdi-plus me-1"></i> Add New Employee</button>
                <br>
                <br>
                <div class="table-responsive">
                    <table class="table dt-responsive activate-select table-striped nowrap w-100 datatable ">
                        <thead class="table-light">
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
                                    <td><?php echo isset($value['employee_name']) ? $value['employee_name'] : NULL;  ?></td>
                                    <td><?php echo isset($value['employee_mobile_no']) ? $value['employee_mobile_no'] : NULL;  ?></td>
                                    <td><?php echo isset($value['employee_email']) ? $value['employee_email'] : NULL;  ?></td>
                                    <td><?php echo isset($value['employee_national_id']) ? $value['employee_national_id'] : NULL;  ?></td>
                                    <td><?php echo isset($value['designation']) ? $value['designation'] : NULL;  ?></td>
                                    <td><?php echo isset($value['employee_address']) ? $value['employee_address'] : NULL;  ?></td>
                                    <td><?php echo isset($value['employee_status']) ? $value['employee_status'] : NULL;  ?></td>
                                    <td class="text-center">
                                        <div class="btn-group">

                                            <button type="button" class="btn btn-warning waves-effect waves-light employee_update_data"
                                                data-emp_id="<?php echo @$value['id'] ?>"
                                                data-name="<?php echo @$value['employee_name'] ?>"
                                                data-phone="<?php echo @$value['employee_mobile_no'] ?>"
                                                data-email="<?php echo @$value['employee_email'] ?>"
                                                data-nid="<?php echo @$value['employee_national_id'] ?>"
                                                data-designation="<?php echo @$value['designation'] ?>"
                                                data-address="<?php echo @$value['employee_address'] ?>"
                                                data-joining_date="<?php echo @$value['joining_date'] ?>"

                                                data-bs-toggle="modal"
                                                data-bs-target="#con-close-modal"><span class="fas fa-edit"></span></button>


                                            <a onclick="return confirm('Are You Sure To Delete This Eployee ?');"
                                                href="?page=employee_view&delete-token=<?php echo isset($value['id']) ? $value['id'] : NULL; ?>"
                                                class="btn btn-danger waves-effect waves-light btn-sm">
                                                Delete <span class="glyphicon glyphicon-remove"></span>
                                            </a>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>









<!--zone  modal content -->
<div id="con-close-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">expence</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-12">

                            <input type="hidden" name="em_id" id="em_id">

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
                                <label for="joining_date" class="form-label">Employee's National Id</label>
                                <input type="date" id="joining_date" name="joining_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="address" class="form-label">Employee's Address</label>
                                <textarea id="address" name="address" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success waves-effect waves-light btn-lg" name="submit">Save</button>
                    </div>
            </form>
        </div>
    </div>
</div><!-- /.modal -->


<script>
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
</script>