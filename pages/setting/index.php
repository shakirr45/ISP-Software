<?php
date_default_timezone_set('Asia/Dhaka');
$date_time = date('Y-m-d g:i:sA');
//$date        = date('Y-m-d');
$ip_add = $_SERVER['REMOTE_ADDR'];
$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;


$customers = $obj->view_all_by_cond("tbl_agent", "ag_status=1");
$templates = $obj->view_all("tbl_complain_templates");
$apiKey = $obj->getSettingValue('sms', 'pass');
$sender = $obj->getSettingValue('sms', 'sender');
$smsUser = $obj->getSettingValue('sms', 'user');
$employees = $obj->view_all_by_cond('tbl_employee', 'employee_status=1');

// var_dump($byId);
// exit();
if (isset($_POST['submit'])) {

    // Input theke value newa
    $smsApiKey = $_POST['sms_api_key'];
    $smsSender = $_POST['sms_sender_number'];
    $smsUsername = $_POST['sms_user_name'];

    // Empty check
    if ($smsApiKey != '' || $smsSender != '' || $smsUsername != '') {

        // Update each setting
        $obj->updateSettingValue('sms', $smsApiKey, 'pass');
        $obj->updateSettingValue('sms', $smsSender, 'sender');
        $obj->updateSettingValue('sms', $smsUsername, 'user');

        // Notification and redirect
        $obj->notificationStore('Data Updated Successfully.', 'success');
        echo '<script> window.location="?page=setting"; </script>';
    } else {
        $obj->notificationStore('Data Added Failed.', 'success');
        echo '<script> window.location="?page=setting"; </script>';
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
            <h6 class="text-md text-neutral-500">Settiongs</h6>

            <!-- Form Wizard Start -->
            <div class="form-wizard">
                <form enctype="multipart/form-data" method="post">

                    <fieldset class="wizard-fieldset show">
                        <div class="row gy-3">
                          
                            <div class="col-sm-6">
                                <label class="form-label">Sms Api Key</label>
                                <div class="position-relative">
                                    <textarea name="sms_api_key" class="form-control wizard-required" placeholder="Enter Sms Api Key"><?php echo $apiKey ?></textarea>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Sms Sender Number</label>
                                <div class="position-relative">
                                    <textarea name="sms_sender_number" class="form-control wizard-required" placeholder="Enter Sms Sender Number"><?php echo $sender ?></textarea>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Sms User Name</label>
                                <div class="position-relative">
                                    <textarea name="sms_user_name" class="form-control wizard-required" placeholder="Sms User Name"><?php echo $smsUser ?></textarea>
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