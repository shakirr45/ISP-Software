<?php
date_default_timezone_set('Asia/Dhaka');
$date_time = date('Y-m-d g:i:sA');
$date = date('Y-m-d');
$ip_add = $_SERVER['REMOTE_ADDR'];
$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;
$notification = "";
//taking month and years
$day = date('M-Y');

if (isset($_GET['zone']) && !empty($_GET['zone'])) {
    $zone = $_GET['zone'];

    if ($zone == 'x') {

        $smsClient = $obj->view_all_by_cond("tbl_agent", "ag_status='1' AND pay_status='1' AND due_status='0' ");

    } else {


        $smsClient = $obj->view_all_by_cond("tbl_agent", "ag_status='1' and pay_status='1' AND due_status='0'  AND zone = $zone");
        // exit($_GET['zone']);
    }

} else {


    die();
}


$i = '0';
$total_due_amount = 0;
if (isset($_GET['submitbtn']) && $_GET['randcheck'] == $_SESSION['rand']) {
    $_SESSION['rand'] = 2;
    $smsArray = [];
    $smsI = 0;
    foreach ($smsClient as $value) {
        $i++;
        $mobile = isset($value['ag_mobile_no']) ? $value['ag_mobile_no'] : NULL;
        //$ip = isset($value['ip']) ? $value['ip'] : NULL;
        $ip = isset($value['cus_id']) ? $value['cus_id'] : NULL;
        $all_d = $obj->get_customer_dues(isset($value['ag_id']) ? $value['ag_id'] : NULL);
        $value1 = $obj->details_by_cond("sms", "status='1'");

        $sms_h = isset($value1['smshead']) ? $value1['smshead'] : NULL;
        $sms_b = isset($value1['smsbody']) ? $value1['smsbody'] : NULL;

        $mass = "Dear $ip, $sms_h $all_d $sms_b ";
        $mobilenum = "88" . $mobile;
        //     $obj->sendsms($mass, $mobilenum);

        //   $agentId = isset($value['ag_id']) ? $value['ag_id'] : NULL;
        //   $obj->Update_data("tbl_agent", ['sms_sent' => 1], "where ag_id='$agentId'");


        $smsArray[$smsI++] = ["to" => $mobilenum, "message" => $mass];
    }

    // var_dump($smsArray);
    // exit();
    
    $obj->sms_send($smsArray);
}


?>

<script>
    window.location = "?page=due_sms";
</script>