<?php

date_default_timezone_set('Asia/Dhaka');
$date_time = date('Y-m-d g:i:sA');
//$date        = date('Y-m-d');
$ip_add = $_SERVER['REMOTE_ADDR'];
$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

$template = $obj->details_by_cond("tbl_complain_templates", 'id=' . $_GET['token']);

if (isset($_POST['submit'])) {

    $form_template_data = array(
        'template' => $_POST['details']
    );
    $obj->Update_data('tbl_complain_templates', $form_template_data, 'id=' . $_POST['template_id']);

    $obj->notificationStore('Complain Tempalte Edited.', 'success');
    echo '<script> window.location="?page=complain_template"; </script>';
}
?>

<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <!-- Form Wizard Start -->
            <div class="form-wizard">
                <fieldset class="wizard-fieldset show">
                    <div class="row gy-3">
                        <div class="col-md-12 bg-teal-800">
                            <h6 class="h4 mt-4">Edit Complaine Template</h6>
                        </div>
                        <hr>
                        <div class="col-md-12"
                            style="margin-top:5px; margin-bottom: 5px; font-size:14px; color:red; font-weight:bold;">
    <b><?php echo isset($notification) ? $notification : NULL; ?></b>
</div>
<?php $obj->notificationShowRedirect(); ?>


                        <div class="row mb-5">
                            <form role="form" enctype="multipart/form-data" method="post">
                                <div class="col-md-8"> <!-- Left align form fields -->
                                    <div class="form-group text-left">
                <label>Complain Template Details</label>
                <textarea class="form-control" name="details" id="ResponsiveDetelis" rows="6"
                    required><?= $template['template'] ?></textarea>
            </div>
            <input type="hidden" name="template_id" value="<?= $_GET['token'] ?>">
<br>
         <div class="form-group mt-2">
                                        <button type="submit" class="btn btn-success" name="submit">A/dd Complaine Template</button>
                                    </div>
        </div>

    </form>
</div>

</div>
</div>
</div>
</div>
</div>
<script>
    $('select[name="customer_id"]').select2({
        placeholder: "Select",
        allowClear: true,
    });
</script>