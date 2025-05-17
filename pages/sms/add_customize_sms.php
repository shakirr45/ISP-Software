<?php

date_default_timezone_set('Asia/Dhaka');
$date_time = date('Y-m-d g:i:sA');
//$date        = date('Y-m-d');
$ip_add = $_SERVER['REMOTE_ADDR'];
$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

$sms = $obj->details_by_cond("sms", "status='2'");

//===================Add Function===================

if (isset ($_POST['submit'])) {

    extract($_POST);

    $form_data_sms = array(
        'smsbody' => $sms_body,
        'smshead' => $sms_head
    );


    if ($obj->Total_Count('sms', "status='2'") == 1) {

        $smsRow = $obj->Update_data("sms", $form_data_sms, " status='2' ");
    } else {

        $form_data_sms['status'] = 2;

        $smsRow = $obj->Reg_user_cond('sms', $form_data_sms, '');

    }

    if ($smsRow) {

        echo '<script> window.location="?page=add_customize_sms"; </script>';

    } else {

        echo $notification = 'Insert Failed';
    }
}
?>
<div class="d-flex justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <!-- Form Wizard Start -->
                <div class="form-wizard">
                    <fieldset class="wizard-fieldset show">
                        <div class="text-center">
                            <div class="col-md-12 bg-teal-800">
                                <h6 class="text-md text-neutral-500">Add Custom SMS For Individual Customer</h6>
                            </div>
                            <hr>
                            <b><?php echo isset($notification) ? $notification : NULL; ?></b>
                        </div>

                        <form role="form" enctype="multipart/form-data" method="post" class="d-flex flex-column align-items-center w-100">
                            <div class="form-group w-100">
                                <label style="font-size:14px">Custom SMS Header</label>
                                <textarea class="form-control mt-1" onkeyup="countCharH(this)" name="sms_head"
                                          id="ResponsiveDetelis" rows="2" style="width: 100%;"><?php echo isset($sms['smshead']) ? $sms['smshead'] : NULL; ?></textarea>
                                <span id="charNumH"></span>
                            </div>

                            <div class="form-group mt-3 w-100">
                                <label style="font-size:14px">Custom SMS Details</label>
                                <textarea class="form-control mt-1" onkeyup="countCharB(this)" name="sms_body"
                                          id="ResponsiveDetelis" rows="6" style="width: 100%;"><?php echo isset($sms['smsbody']) ? $sms['smsbody'] : NULL; ?></textarea>
                                <span id="charNumB"></span>
                            </div>

                            <div class="form-group text-center">
                                <button type="submit" class="btn btn-success mt-4" name="submit">SAVE SMS</button>
                            </div>
                        </form>  
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
     function countCharH(val) {
        var len = val.value.length;
        if (len >= 60) {
          val.value = val.value.substring(0, 60);
        } else {
          $('#charNumH').text(60 - len);
        }
      };
      function countCharB(val) {
        var len = val.value.length;
        if (len >= 160) {
          val.value = val.value.substring(0, 160);
        } else {
          $('#charNumB').text(160 - len);
        }
      };
</script>