<?php include('customer.php') ?>

<form action="" method="POST" id="addUser" enctype="multipart/form-data">
    <div class="row gy-4">
        <div class="col-md-12 my-20">
            <div class="card">
                <div class="card-body">
                    <!-- Form Wizard Start -->
                    <div class="form-wizard">
                        <fieldset class="wizard-fieldset show">
                            <h6 class="text-md text-neutral-500">Connect Mikrotik</h6>
                            <div class="row gy-3">
                                <?php if ($activeMikrotik): ?>
                                    <div class="col-sm-9"></div>
                                    <div class="col-sm-3">
                                        <label class="form-label">Mikrotik List </label>
                                        <select class="form-control" id="mikrotikuserid" name="mikrotik_id">
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
                        </fieldset>

                    </div>
                    <!-- Form Wizard End -->
                </div>
            </div>
        </div>
        <?php if ($activeMikrotik): ?>
            <?php if ($mikrotikget == 0): ?>
                <div class="col-md-12 my-20">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-4 text-center text-danger text-xl">Please Select Mikrotik</h6>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($mikrotikConnect): ?>
                <div class="col-md-12 my-20">
                    <div class="card">
                        <div class="card-body">
                            <fieldset class="wizard-fieldset">
                                <div class="row">
                                    <div class="col-md-9">
                                        <h6 class="text-md text-neutral-500">Customer Information</h6>
                                    </div>
                                    <div class="col-md-3">
                                        <h5 class="text-md text-success-600">Successfully Conected</h5>
                                    </div>
                                </div>
                                <div class="row gy-3">
                                    <div class="col-sm-6">
                                        <label class="form-label">PPPOE User*</label>
                                        <div class="position-relative">
                                            <input type="text" class="form-control wizard-required"
                                                id="ip" name="ip" placeholder="Enter IP" required>
                                            <div class="wizard-form-error"></div>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label class="form-label">Mikrotik Secret Password*</label>
                                        <div class="position-relative">
                                            <input type="text" class="form-control wizard-required" id="queue_password"
                                                name="queue_password" placeholder="Enter Password" required>
                                            <div class="wizard-form-error"></div>
                                        </div>
                                    </div>

                                </div>
                            </fieldset>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($checkconenctM): ?>

            <div class="col-md-6 my-20">
                <div class="card">
                    <div class="card-body">
                        <fieldset class="wizard-fieldset show">
                            <h6 class="text-md text-neutral-500">Customer Personal Information</h6>
                            <div class="row gy-3">
                                <?php if (!$mikrotikConnect): ?>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="ip" class="form-label">PPPOE User*</label>
                                            <input type="text" id="ip" name="ip" class="form-control" required>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="col-sm-6">
                                    <label class="form-label">Full Name*</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control wizard-required" id="ag_name" name="ag_name"
                                            placeholder="Enter Name" required>
                                        <div class="wizard-form-error"></div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Mobile*</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control wizard-required" onkeypress="return numbersOnly(event)"
                                            id="ag_mobile_no" name="ag_mobile_no"
                                            placeholder="Enter Mobile Number" required>
                                        <div class="wizard-form-error"></div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Others Mobile</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control wizard-required" onkeypress="return numbersOnly(event)"
                                            id="regular_mobile" name="regular_mobile"
                                            placeholder="Enter Other Mobile Number">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Email</label>
                                    <div class="position-relative">
                                        <input type="email" class="form-control wizard-required" id="ag_email" name="ag_email"
                                            placeholder="Enter Email">

                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Gender</label>
                                    <div class="position-relative">
                                        <select class="form-control wizard-required" id="gender" name="gender">
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Onu Mac Address</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control wizard-required" id="onumac" name="onumac">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">NID/Passport No</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control wizard-required" id="national_id" name="national_id">
                                        <div class="wizard-form-error"></div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <label class="form-label">NID/Passport Photo</label>
                                    <div class="position-relative">
                                        <input type="file" class="form-control wizard-required" id="nationalidphoto" name="nationalidphoto">
                                    </div>
                                </div>
                                <!--<div class="col-sm-3">-->
                                <!--    <div class="position-relative">-->
                                <!--        <img src="" height="100px" width="100px" id="img">-->
                                <!--    </div>-->
                                <!--</div>-->
                                <div class="col-sm-12">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" id="address" class="form-control wizard-required"></textarea>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Fiber Code/Id</label>
                                    <input type="text" class="form-control wizard-required" id="fibercode" name="fibercode">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Agent Type*</label>
                                    <select class="form-control wizard-required" id="agent_type" name="agent_type" required>
                                        <option value="Optical Fiber">Optical Fiber</option>
                                        <option value="Cat 5">Cat 5</option>
                                    </select>
                                    <div class="wizard-form-error"></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Connection Type*</label>
                                    <select class="form-control wizard-required" id="connectiontype" name="connectiontype" required>
                                        <option value="Home">Home</option>
                                        <option value="Corporate">Corporate</option>
                                    </select>
                                    <div class="wizard-form-error"></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Connection Date*</label>
                                    <input type="date" class="form-control wizard-required" id="connection_date" name="connection_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    <div class="wizard-form-error"></div>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>

            <div class="col-md-6 my-20">
                <div class="card">
                    <div class="card-body">
                        <div class="form-wizard">
                            <fieldset class="wizard-fieldset show">
                                <h6 class="text-md text-neutral-500">Customer Billing Information</h6>
                                <div class="row gy-3">
                                    <div class="col-sm-6">
                                        <label class="form-label">MB*</label>
                                        <select name="mb" id="mb" class="form-control" required>
                                            <option value="">Select</option>
                                            <?php foreach ($obj->getAllData("tbl_package", ($mikrotikget > 0) ? ['where' => [['type', '=', 1], ['mikrotik_id', '=', $mikrotikget]]] : ['where' => ['type', '=', 1]]) as $value): ?>
                                                <option data-bill="<?php echo $value['bill_amount'] ?>" value="<?php echo $value['net_speed'] ?>"><?php echo $value['package_name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="wizard-form-error"></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Monthly Bill Amount*</label>
                                        <input type="text" class="form-control wizard-required" onkeypress="return numbersOnly(event)" id="taka" name="taka" value="0" required>
                                        <div class="wizard-form-error"></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Effected From Current Month Bill</label>
                                        <input type="checkbox" class="form-check-input" id="effected" name="effected">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Running Month Paid Amount*</label>
                                        <input type="text" class="form-control wizard-required" id="runningpaid" name="runningpaid" value="0" onkeypress="return numbersOnly(event)" required>
                                        <div class="wizard-form-error"></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Connection Fee Paid Amount*</label>
                                        <input type="text" class="form-control wizard-required" id="connect_charge" name="connect_charge" value="0" class="form-control" onkeypress="return numbersOnly(event)" required>
                                        <div class="wizard-form-error"></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Disconenct Day*</label>
                                        <input type="text" min="1" max="32" id="mikrotik_disconnect" name="mikrotik_disconnect" class="form-control" onkeypress="return numbersOnly(event)" required>
                                        <div class="wizard-form-error"></div>
                                    </div>
                                    <div class="col-sm-12">
                                        <label class="form-label">Zone*</label>
                                        <select name="zone" id="zone" class="form-control wizard-required" required>
                                            <option value="">Select</option>
                                            <?php foreach ($obj->getAllData("tbl_zone", ['where' => ['level', '=', '1']]) as $value): ?>
                                                <option value="<?php echo $value['zone_id']; ?>"><?php echo $value['zone_name']; ?> </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="wizard-form-error"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="zonesub"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="destination"> </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Billing Person*</label>
                                        <select name="billing_person_id" id="billing_person_id" class="form-control wizard-required" required>
                                            <option value="">Select</option>
                                            <?php foreach ($obj->getAllData("vw_user_info") as $value): ?>
                                                <option value="<?php echo $value['UserId']; ?>"><?php echo $value['FullName']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="wizard-form-error"></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Status*</label>
                                        <select class="form-control wizard-required" id="ag_status" name="ag_status" required>
                                            <option value="1">Active</option>
                                            <option value="0">InActive</option>
                                            <option value="2">Free</option>
                                            <option value="3">Discontinue</option>
                                        </select>
                                        <div class="wizard-form-error"></div>
                                    </div>
                                    <div class="col-sm-12">
                                        <label class="form-label">Remarks</label>
                                        <textarea id="remark" name="remark" class="form-control"></textarea>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-switch switch-success d-flex align-items-center gap-3">
                                            <input class="form-check-input" type="checkbox" role="switch" id="smssend" name="smssend" value="smssend" checked>
                                            <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="yes">SMS Notification Send</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <button type="submit" class="btn btn-success-600 radius-8 px-20 py-11" name="submit">Create</button>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>

</form>

<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {
        $('#mikrotikuserid').on('change', function() {
            var mid = $(this).val();
            console.log('mfghid');

            if (mid > 0) {
                window.location.href = "?page=customer_create&mikrotik=" + mid;
            } else {
                window.location.href = "?page=customer_create";
            }
        });
         $('#ip').focusout(function() {
            var ip = $(this).val();
            // alert(ip)
            if (ip.length > 0) {
                $.get("./pages/customer/ip_ajax.php", {
                    ip: ip
                }, function(result) {
                    // alert(result.status);
                    if (result == 0) {
                        Swal.fire({
                            icon: "error",
                            title: 'Sorry this Client Id is already exists.',
                            showConfirmButton: true,
                        });
                        $('#ip').val('');
                    }
                });
            }
        });

        $('#zone').on('change', function() {
            $('#zonesub').html('');
            $('#destination').html('');
            $.get("./pages/others/zone_ajax.php", {
                zone_id: $(this).val()
            }, function(result) {
                $('#zonesub').html(result);

                $('#sub_id').on('change', function() {
                    $('#destination').html('');
                    $.get("./pages/others/zone_ajax.php", {
                        subzone_id: $(this).val()
                    }, function(result) {
                        $('#destination').html(result);
                    });
                });

            });
        });

        $('#mb').on('change', function() {
            $('#taka').val($('#mb option:selected').data('bill'));
        });



        $("#nationalidphoto").change(function() {
            var input = this; // Assign 'this' to input
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#img').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        });

    });
</script>
<?php $obj->end_script(); ?>