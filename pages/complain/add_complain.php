<?php
date_default_timezone_set('Asia/Dhaka');
$date_time = date('Y-m-d g:i:sA');
//$date        = date('Y-m-d');
$ip_add = $_SERVER['REMOTE_ADDR'];
$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;





$customers = $obj->view_all_by_cond("tbl_agent", "ag_status=1");
$templates = $obj->view_all("tbl_complain_templates");
$employees = $obj->view_all_by_cond('tbl_employee', 'employee_status=1');

if (isset($_POST['submit'])) {

    if (isset($_POST['sms_check'])) {
        $sms_check_cus = $_POST['customer_sms'];
        $sms_check_emp = $_POST['employee_sms'];
    }
    if ($_POST['customer_id'] != '' && $_POST['employee_id'] != '' && $_POST['customer_id'] != '') {
        $employee_id = $_POST['employee_id'];
        $complain_type = $_POST['complain_type'];
        $support_employees = $_POST['support_employees'];
        $priority = $_POST['priority'];
        $solve_employee_info = $obj->details_by_cond("tbl_employee", "id=" . $employee_id);
        $employee_mobile = $solve_employee_info['employee_mobile_no'];
        $customer_id = $_POST['customer_id'];
        $customer_info = $obj->details_by_cond('tbl_agent', 'ag_id=' . $customer_id);
        $customer_mobile = $customer_info['ag_mobile_no'];
        $customer_ip = $customer_info['ip'];

        $complain = $_POST['details'];
        //$complain_date = $_POST['complain_date'];

        $form_complain_date = array(
            'details' => $_POST['details'],
            'note' => $_POST['note'],
            'customer_id' => $_POST['customer_id'],
            'status' => 1,
            'complain_type' => $complain_type,
            'priority' => $priority,
            'solve_by' => $_POST['employee_id'],
            'sub_solve_by' => isset($support_employees) ? implode(',', $support_employees) : "",
            'entry_by' => $userid,
            'update_by' => $userid,
            'complain_date' => date('Y-m-d H:i:s', strtotime($date_time)),
            'entry_date' => date('Y-m-d h:i:s')
        );
        $obj->Insert_data('tbl_complains', $form_complain_date);



        $postUrl = "http://api.bulksms.icombd.com/api/v3/sendsms/xml";
        $smsbody_cus = "Welcome to " . $obj->getSettingValue('sms', 'company_name') . ". Cust.ID : $customer_ip, Complain : " . $complain . " on has been noted and will be solved as very soon. Assigned to Support - " . $employee_mobile . ". Thank you.";

        //for customer who has complain
        if (isset($sms_check_cus)) { // sms will not send if the sms notification checkbox is unchecked
            $mobilenumCustomer = "88" . $customer_mobile;
            $obj->sendsms($mobilenumCustomer, $smsbody_cus);
        }

        //for employee who will solve
        $smsbody_emp = $obj->getSettingValue('sms', 'company_name') . " Support. Cust.ID : $customer_ip, Complain : " . $complain . " on has been taken. Customer Mobile : " . $customer_mobile . ". Thanks.";

        if (isset($sms_check_emp)) { // sms will not send if the sms notification checkbox is unchecked
            $mobilenumEmployee = "88" . $employee_mobile;
            $obj->sendsms($mobilenumEmployee, $smsbody_emp);
        }


        $obj->notificationStore('Customer Complain Added.', 'success');
        echo '<script> window.location="?page=view_complain"; </script>';
    } else {
        $obj->notificationStore('Complain Added Failed.', 'success');
        echo '<script> window.location="?page=add_complain"; </script>';
    }
}
?>
<style>
    .selection {
        width: 100%;
    }
</style>
<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <h6 class="text-md text-neutral-500">Complain Information</h6>

            <!-- Form Wizard Start -->
            <div class="form-wizard">
                <form enctype="multipart/form-data" method="post">

                    <fieldset class="wizard-fieldset show">
                        <div class="row gy-3">
                            <div class="col-sm-6">
                                <label class="form-label">Select Customer*</label>
                                <div class="position-relative">
                                    <select name="customer_id" id="customer_id" class="form-control wizard-required" required>
                                        <option value="">Select</option>
                                        <?php foreach ($customers as $customer) { ?>
                                            <option value="<?= $customer['ag_id'] ?>"><?= $customer['ag_name']; ?> (<?= $customer['ip']; ?>)
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <div class="wizard-form-error"></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Complain Template*</label>
                                <div class="position-relative">
                                    <select name="complain_type" class="form-control wizard-required">
                                        <option value="">Select Template</option>
                                        <?php foreach ($templates as $template) { ?>
                                            <option value="<?= $template['id']; ?>"><?= $template['template']; ?></option>
                                        <?php } ?>
                                    </select>
                                    <div class="wizard-form-error"></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Priority*</label>
                                <div class="position-relative">
                                    <select name="priority" class="form-control wizard-required" required>
                                        <option value="1">High</option>
                                        <option value="2">Medium</option>
                                        <option value="3">Low</option>
                                    </select>
                                    <div class="wizard-form-error"></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Complain Details</label>
                                <div class="position-relative">
                                    <textarea name="details" class="form-control wizard-required" placeholder="Enter Complain Details"></textarea>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <label class="form-label">Note</label>
                                <div class="position-relative">
                                    <textarea name="note" class="form-control wizard-required" id="note"></textarea>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Employee For Solve</label>
                                <div class="position-relative">
                                    <select name="employee_id" id="employee_id" class="form-control wizard-required" required>
                                        <option value="">Select Employee</option>
                                        <?php foreach ($employees as $employee) { ?>
                                            <option value="<?= $employee['id']; ?>"><?= $employee['employee_name']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Select Multiple Support Employees</label>
                                <div class="position-relative">
                                    <select name="support_employees[]" id="support_employees" class="form-control wizard-required" multiple required>
                                        <option value="">Select Employee</option>
                                        <?php foreach ($employees as $employee) { ?>
                                            <option value="<?= $employee['id']; ?>"><?= $employee['employee_name']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-switch switch-success d-flex align-items-center gap-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="customer_sms" name="customer_sms" value="smssend" checked>
                                    <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="yes">Customer SMS</label>
                                </div>
                                <br>
                                <div class="form-switch switch-success d-flex align-items-center gap-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="employee_sms" name="employee_sms" value="smssend" checked>
                                    <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="yes">Assigned Employee SMS</label>
                                </div>
                            </div>
                            <div class="form-group text-end">
                                <button type="submit" name="submit" class="form-wizard-next-btn btn btn-success-600 px-32">Submit</button>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
            <!-- Form Wizard End -->
        </div>
    </div>
</div>
<?php $obj->start_script(); ?>
<script>
    $(document).ready(function() {



        // Initialize select2 for multiple select
        $('#support_employees').select2({
            placeholder: "Choose employees...",
        });


    });

    /*$('input[name="complain_date"]').datepicker({
        autoclose: true,
        toggleActive: true,
        format: 'dd-mm-yyyy'
    // });*/
    $('input[name="complain_time"]').timepicker();
</script>

<!-- Init js-->
<script src="assets/js/pages/form-advanced.init.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Add Select2 JS (at the end of your body tag) -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php $obj->end_script(); ?>