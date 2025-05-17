<div class="container-fluid mt-3">
    <form action="" method="POST" id="addUser" enctype="multipart/form-data">

        <div class="row">
            <div class="col-md-10">
                <div class="card-body">
                    <h4 class="header-title">Customer </h4>
                    <p class="sub-header"> Add New Customer </p>
                </div>
            </div>
            <?php if ($activeMikrotik): ?>
                <div class="col-md-2">
                    <label for="" class="form-label">Mikrotik List </label>
                    <select class="form-select" id="mikrotikuserid" name="mikrotik_id">
                        <option value="">Select</option>
                        <?php $allMikrotik = $obj->getAllData('mikrotik_user', ['where' => ['status', '=', 1]]);
                        if ($allMikrotik):
                            foreach ($allMikrotik as $mikrotiklist) :
                                echo '<option ' . (($mikrotikget == $mikrotiklist['id']) ? 'selected' : '') . ' value="' . htmlspecialchars($mikrotiklist['id']) . '">' . htmlspecialchars($mikrotiklist['mik_ip']) . '</option>';
                            endforeach;
                        endif;
                        ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($activeMikrotik): ?>


            <?php if ($mikrotikget == 0): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h3 class="text-danger text-center">Please Select Mikrotik</h3>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif (!$mikrotikConnect): ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h3 class="text-danger text-center">Sorry We could not connect Miktotik.Please Check IP/API PORT/USERNAME/PASSWORD.Try Again.</h3>
                                <div class="progress mb-0">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 75%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($mikrotikConnect): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card widget-inline">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-9">
                                        <h4>Mikrotik</h4>
                                    </div>
                                    <div class="col-md-3">
                                        <h4 class="text-success text-right">Successfully Conected</h4>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="p-2">
                                            <div class="mb-3">
                                                <label for="ip" class="form-label">PPPOE User*</label>
                                                <input type="text" id="ip" name="ip" class="form-control" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-2">
                                            <div class="mb-3">
                                                <label for="queue_password" class="form-label">Mikrotik Secret Password*</label>
                                                <input type="text" id="queue_password" name="queue_password" class="form-control" required>
                                            </div>
                                        </div>
                                    </div>
                                </div> <!-- end row -->
                            </div>
                        </div> <!-- end card-->
                    </div> <!-- end col-->
                </div>
            <?php endif; ?>

        <?php endif; ?>


        <?php if ($checkconenctM): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4>Customer Personal Info</h4>
                            <div class="row">
                                <?php if (!$mikrotikConnect): ?>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="ip" class="form-label">PPPOE User*</label>
                                            <input type="text" id="ip" name="ip" class="form-control" required>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="ag_name" class="form-label">Full Name*</label>
                                        <input type="text" id="ag_name" name="ag_name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ag_mobile_no" class="form-label">Mobile*</label>
                                        <input type="text" id="ag_mobile_no" name="ag_mobile_no" class="form-control" onkeypress="return numbersOnly(event)" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="regular_mobile" class="form-label">Others Mobile</label>
                                        <input type="text" id="regular_mobile" name="regular_mobile" onkeypress="return numbersOnly(event)" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ag_email" class="form-label">Email</label>
                                        <input type="email" id="ag_email" name="ag_email" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="gender" class="form-label">Gender </label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea name="address" id="address" class="form-control"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="national_id" class="form-label">NID/Passport No </label>
                                        <input type="text" id="national_id" name="national_id" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="nationalidphoto" class="form-label">NID/Passport Photo</label>
                                        <input type="file" id="nationalidphoto" name="nationalidphoto" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <img src="" height="100px" id="img" width="100px" alt="img">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="onumac" class="form-label">Onu Mac Address </label>
                                        <input type="text" id="onumac" name="onumac" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="fibercode" class="form-label">Fiber Code/Id</label>
                                        <input type="text" id="fibercode" name="fibercode" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="agent_type" class="form-label">Client Type*</label>
                                        <select class="form-select" id="agent_type" name="agent_type" required>
                                            <option value="Optical Fiber">Optical Fiber</option>
                                            <option value="Cat 5">Cat 5</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="connectiontype" class="form-label">Connection Type*</label>
                                        <select class="form-select" id="connectiontype" name="connectiontype" required>
                                            <option value="Home">Home</option>
                                            <option value="Corporate">Corporate</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="connection_date" class="form-label">Connection Date*</label>
                                        <input type="date" id="connection_date" name="connection_date" value="<?php echo date('Y-m-d'); ?>" class="form-control">
                                    </div>
                                </div>
                            </div> <!-- end row -->
                        </div>
                    </div> <!-- end card-->
                </div> <!-- end col-->

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4>Customer Billing Info</h4>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="mb" class="form-label">Package*</label>
                                        <select name="mb" id="mb" class="form-control" required>
                                            <option value="">Select</option>
                                            <?php foreach ($obj->getAllData("tbl_package", ($mikrotikget > 0) ? ['where' => [['type', '=', 1], ['mikrotik_id', '=', $mikrotikget]]] : ['where' => ['type', '=', 1]]) as $value): ?>
                                                <option data-bill="<?php echo $value['bill_amount'] ?>" value="<?php echo $value['net_speed'] ?>"><?php echo $value['package_name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="taka" class="form-label">Monthly Bill Amount</label>
                                        <input type="text" id="taka" name="taka" class="form-control" onkeypress="return numbersOnly(event)" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="effected" class="form-label">Effected From Current Month Bill</label>
                                        <input type="checkbox" id="effected" name="effected" class="form-check-input">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="runningpaid" class="form-label">Running Month Paid Amount</label>
                                        <input type="text" id="runningpaid" name="runningpaid" value="0" onkeypress="return numbersOnly(event)" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="connect_charge" class="form-label">Connection Fee Paid Amount</label>
                                        <input type="text" id="connect_charge" name="connect_charge" value="0" class="form-control" onkeypress="return numbersOnly(event)" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mikrotik_disconnect" class="form-label">Disconenct Day*</label>
                                        <input type="text" min="1" max="32" id="mikrotik_disconnect" name="mikrotik_disconnect" class="form-control" onkeypress="return numbersOnly(event)" required>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="zone" class="form-label">Zone*</label>
                                        <select name="zone" id="zone" class="form-control" required>
                                            <option value="">Select</option>
                                            <?php foreach ($obj->getAllData("tbl_zone", ['where' => ['level', '=', '1']]) as $value): ?>
                                                <option value="<?php echo $value['zone_id'] ?>"><?php echo $value['zone_name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div id="zonesub"></div>
                                </div>
                                <div class="col-md-6">
                                    <div id="destination"> </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="billing_person_id" class="form-label">Billing Person*</label>
                                        <select name="billing_person_id" id="billing_person_id" class="form-control" required>
                                            <option value="">Select</option>
                                            <?php foreach ($obj->getAllData("vw_user_info") as $value): ?>
                                                <option value="<?php echo $value['UserId'] ?>"><?php echo $value['FullName'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ag_status" class="form-label">Status*</label>
                                        <select class="form-select" id="ag_status" name="ag_status" required>
                                            <option value="1">Active</option>
                                            <option value="0">InActive</option>
                                            <option value="2">Free</option>
                                            <option value="3">Discontinue</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="remark" class="form-label">Remarks</label>
                                        <textarea id="remark" name="remark" class="form-control"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <br>
                                    <br>
                                    <div class="form-check mb-3 form-check-primary">
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
                                    <button type="submit" class="btn btn-success waves-effect waves-light btn-lg" name="submit">Save</button>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end card-->
                </div> <!-- end col-->
            </div>
        <?php endif; ?>
    </form>
</div>