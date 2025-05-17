<?php

date_default_timezone_set('Asia/Dhaka');
$date_time = date('Y-m-d g:i:sA');
//$date        = date('Y-m-d');
$ip_add = $_SERVER['REMOTE_ADDR'];
$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

$complain = $obj->details_by_cond('tbl_complains', 'id=' . $_GET['id']);
$complain_new = $obj->details_by_cond('tbl_complains_new_user', 'id=' . $_GET['id']);

$complain_customer = $obj->details_by_cond('tbl_agent', 'ag_id=' . $complain['customer_id']);

$customers = $obj->view_all_by_cond("tbl_agent", "ag_status=1");
$templates = $obj->view_all("tbl_complain_templates");
$employees = $obj->view_all_by_cond('tbl_employee', 'employee_status=1');


if (isset($_POST['submit'])) {

    $form_complain_date = array(
        'details' => $_POST['details'],
        'note' => $_POST['note'],
        'priority' => $_POST['priority'],
        'complain_type' => $_POST['complain_type'],
        'customer_id' => $_POST['customer_id'],
        'solve_by' => $_POST['employee_id'],
        'sub_solve_by' => isset($_POST['support_employees']) ? implode(',', $_POST['support_employees']) : "",
        'update_by' => $userid,
        'complain_date' => date('Y-m-d H:i:s', strtotime($_POST['complain_date'] . ' ' . $_POST['complain_time'])),
        'solve_date' => date('Y-m-d H:i:s', strtotime($date_time))
    );
    $obj->Update_data('tbl_complains', $form_complain_date, 'id=' . $complain['id']);
    $obj->notificationStore('Customer Complain Edited.', 'success');

    echo '<script> window.location="?page=view_complain"; </script>';
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
            <h6 class="mb-4 text-xl">Edit Customer Complaint</h6>

            <!-- Form Wizard Start -->
            <div class="form-wizard">
                <form enctype="multipart/form-data" method="post">

                    <fieldset class="wizard-fieldset show">
                        <div class="row gy-3">
                            <div class="col-sm-6">
                                <label class="form-label">Complain Date</label>
                                <div class="position-relative">
                                    <input type="date" name="complain_date" class="form-control wizard-required" value="<?php echo date('Y-m-d', strtotime($complain['complain_date'])); ?>" required>
                                    <div class="wizard-form-error"></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Complain Time</label>
                                <div class="position-relative">
                                    <input type="text" name="complain_time"
                                        value="<?= date('h:i A', strtotime($complain['complain_date'])) ?>" class="form-control"
                                        autocomplete="off">
                                    <div class="wizard-form-error"></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Select Customer*</label>
                                <div class="position-relative">
                                    <select name="customer_id" id="customer_id" class="form-control wizard-required" required>
                                        <option value="">Select</option>
                                        <?php foreach ($customers as $customer) { ?>
                                            <option value="<?= $customer['ag_id'] ?>" <?php if ($customer['ag_id'] == $complain_customer['ag_id']) {
                                                                                            echo 'selected';
                                                                                        } ?>>
                                                <?= $customer['ag_name']; ?> (<?= $customer['cus_id']; ?>)
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
                                            <option value="<?= $template['id']; ?>" <?php
                                                                                    if ($template['id'] == $complain['complain_type']) {
                                                                                        echo 'selected';
                                                                                    }
                                                                                    ?>><?= $template['template']; ?></option>
                                        <?php } ?>
                                    </select>
                                    <div class="wizard-form-error"></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Priority*</label>
                                <div class="position-relative">
                                    <select name="priority" class="form-control wizard-required" required>
                                        option <?= $complain["priority"] == 1 ? "selected" : "" ?> value="1">High</option>
                                        <option <?= $complain["priority"] == 2 ? "selected" : "" ?> value="2">Medium</option>
                                        <option <?= $complain["priority"] == 3 ? "selected" : "" ?> value="3">Low</option>
                                    </select>
                                    <div class="wizard-form-error"></div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Complain Details</label>
                                <div class="position-relative">
                                    <textarea name="details" class="form-control wizard-required" placeholder="Enter Complain Details"><?= $complain['details'] ?></textarea>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <label class="form-label">Note</label>
                                <div class="position-relative">
                                    <textarea name="note" class="form-control wizard-required" id="note"><?= $complain['note'] ?></textarea>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Employee For Solve</label>
                                <div class="position-relative">
                                    <select name="employee_id" id="employee_id" class="form-control wizard-required" required>
                                        <option value="">Select Employee</option>
                                        <?php foreach ($employees as $employee) { ?>
                                            <option
                                                value="<?= $employee['id']; ?>"
                                                <?php
                                                if ($employee['id'] == $complain['solve_by']) {
                                                    echo 'selected';
                                                }
                                                ?>>
                                                <?= $employee['employee_name']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Select Multiple Support Employees</label>
                                <div class="position-relative">
                                    <select name="support_employees[]" id="support_employees" class="form-control wizard-required" multiple required>
                                        <option value="">Select Employee</option>
                                        <?php
                                        $selected_employees = explode(',', $complain["sub_solve_by"]);
                                        foreach ($employees as $employee) {
                                        ?>
                                            <option
                                                value="<?= $employee['id']; ?>"
                                                <?= in_array($employee['id'], $selected_employees) ? "selected" : "" ?>>
                                                <?= $employee['employee_name']; ?>
                                            </option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group text-end">
                                <button type="submit" name="submit" class="form-wizard-next-btn btn btn-primary-600 px-32">Submit</button>
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
    $('input[name="complain_date"]').datepicker({
        autoclose: true,
        toggleActive: true,
        format: 'dd-mm-yyyy'
    });

    $('input[name="solve_date"]').datepicker({
        autoclose: true,
        toggleActive: true,
        format: 'dd-mm-yyyy'
    });
    $('input[name="complain_time"]').timepicker();
    $('input[name="solve_time"]').timepicker();
</script>
<?php $obj->end_script(); ?>