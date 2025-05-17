<?php include('employee.php');

$editToken = isset($_GET['token']) ? $_GET['token'] : null;
$singleEmployee = $obj->rawSqlSingle("SELECT * FROM tbl_employee WHERE id='$editToken'");

?>
<div class="container-fluid align-items-center justify-content-center mt-3">
    <form class="needs-validation" novalidate action="" method="POST" id="addUser" enctype="multipart/form-data">

        <div class="row">
            <div class="col-md-10">
                <div class="card-body">
                    <h4 class="header-title">Employee</h4>
                    <p class="sub-header"> Update New Employee </p>
                </div>
            </div>

        </div>

        <div class="row">


            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4>Employee Info</h4>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Employee's Name</label>
                                    <input type="text" id="name" name="name" value="<?php echo ($singleEmployee) ? $singleEmployee['employee_name'] : '' ?>" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Employee's Mobile No</label>
                                    <input type="text" id="phone" name="phone" value="<?php echo ($singleEmployee) ? $singleEmployee['employee_mobile_no'] : '' ?>" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Employee's Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo ($singleEmployee) ? $singleEmployee['employee_email'] : '' ?>" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nid" class="form-label">Employee's National Id</label>
                                    <input type="text" value="<?php echo ($singleEmployee) ? $singleEmployee['employee_national_id'] : '' ?>" id="nid" name="nid" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="designation" class="form-label">Employee's Designation</label>
                                    <input type="text" value="<?php echo ($singleEmployee) ? $singleEmployee['designation'] : '' ?>" id="designation" name="designation" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="joining_date" class="form-label">Employee's Joining Date</label>
                                    <input type="date" value="<?php echo ($singleEmployee) ? $singleEmployee['joining_date'] : '' ?>" id="joining_date" name="joining_date" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="activeStatus" class="form-label">Active Status</label>
                                    <select id="activeStatus" name="activeStatus" class="form-control">
                                        <option value="1" <?php echo $singleEmployee['employee_status'] == '1' ? "selected" : ""; ?>>Active</option>
                                        <option value="0" <?php echo $singleEmployee['employee_status'] == '0' ? "selected" : ""; ?>>Inactive</option>


                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <input type="hidden" name="em_id" value="<?php echo ($singleEmployee) ? $singleEmployee['id'] : '' ?>">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Employee's Address</label>
                                    <textarea id="address" name="address" class="form-control"><?php echo $singleEmployee['employee_address'] ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <br>
                                <br>
                                <div class="form-check checked-success d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" name="smssend" value="smssend" id="smssend" checked>
                                    <label class="form-check-label" for="smssend">SMS Notification Send</label>
                                </div>
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
                                <button type="submit" class="btn btn-success waves-effect waves-light btn-lg" name="update">Save</button>
                            </div>
                        </div>
                    </div>
                </div> <!-- end card-->
            </div> <!-- end col-->
        </div>

    </form>
</div>