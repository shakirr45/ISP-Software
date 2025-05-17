<?php


include("dbconfig_robotisp.php");
include '../services/Model.php';

$obj = new Model();

date_default_timezone_set('Asia/Dhaka');
$date_time = date('Y-m-d g:i:sA');
//$date        = date('Y-m-d');
$ip_add = $_SERVER['REMOTE_ADDR'];
$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

$customers = $obj->view_all_by_cond("tbl_agent", "ag_status=1");
$templates = $obj->view_all("tbl_complain_templates");
$employees = $obj->view_all_by_cond('tbl_employee', 'employee_status=1');

if ($_SERVER['REQUEST_METHOD']=='POST') {

    // if (isset($_POST['sms_check'])) {
    //     $sms_check_cus = $_POST['customer_sms'];
    //     $sms_check_emp = $_POST['employee_sms'];
    // }
    if ($_POST['customer_id'] != '' && $_POST['employee_id'] != '' && $_POST['customer_id'] != '') {
        $employee_id = $_POST['employee_id'];
        $complain_type = $_POST['complain_type'];
        // $support_employees = $_POST['support_employees'];
        $priority = $_POST['priority'];
        $solve_employee_info = $obj->details_by_cond("tbl_employee", "id=" . $employee_id);
        $employee_mobile = $solve_employee_info['employee_mobile_no'];
        $customer_id = $_POST['customer_id'];
        $customer_info = $obj->details_by_cond('tbl_agent', 'ag_id=' . $customer_id);
        $customer_mobile = $customer_info['ag_mobile_no'];
        $customer_ip = $customer_info['ip'];
        $complain = $_POST['details'];
        $userid = $_POST["user_id"];
        $sms_check_cus = $_POST['customer_sms'];
        $sms_check_emp = $_POST['employee_sms'];
        //$complain_date = $_POST['complain_date'];
        $sub_solve_by =  $_POST["sub_solve_by"];

        if($sub_solve_by == ""){
            $sub_solve_by = "";
        }else{
            $sub_solve_by =  $_POST["sub_solve_by"];
        }

        $form_complain_date = array(
            'details' => $_POST['details'],
            'note' => $_POST['note'],
            'customer_id' => $_POST['customer_id'],
            'status' => 1,
            'complain_type' => $complain_type,
            'priority' => $priority,
            'solve_by' => $_POST['employee_id'],
            'sub_solve_by' => $_POST['sub_solve_by'],
            'entry_by' => $userid,
            'update_by' => $userid,
            'complain_date' => date('Y-m-d H:i:s', strtotime($date_time)),
            'entry_date' => date('Y-m-d h:i:s')
        );
        $obj->Insert_data('tbl_complains', $form_complain_date);

        $postUrl = "http://api.bulksms.icombd.com/api/v3/sendsms/xml";
        $smsbody_cus = "Welcome to " . $obj->getSettingValue('sms', 'company_name') . ". Cust.ID : $customer_ip, Complain : " . $complain . " on has been noted and will be solved as very soon. Assigned to Support - " . $employee_mobile . ". Thank you.";

        //for customer who has complain
        // if (isset($sms_check_cus)) { // sms will not send if the sms notification checkbox is unchecked
        //     $mobilenumCustomer = "88" . $customer_mobile;
        //     $obj->sendsms($mobilenumCustomer, $smsbody_cus);
        // }

        if($sms_check_cus == "1"){
            $mobilenumCustomer = "88" . $customer_mobile;
            $obj->sendsms($mobilenumCustomer, $smsbody_cus);
        }else if($sms_check_cus == "0"){
            echo "3";
        }

        //for employee who will solve
        $smsbody_emp = $obj->getSettingValue('sms', 'company_name') . " Support. Cust.ID : $customer_ip, Complain : " . $complain . " on has been taken. Customer Mobile : " . $customer_mobile . ". Thanks.";

        if ($sms_check_emp == "1") { // sms will not send if the sms notification checkbox is unchecked
            $mobilenumEmployee = "88" . $employee_mobile;
            $obj->sendsms($mobilenumEmployee, $smsbody_emp);
        }else if($sms_check_emp == "0"){
            echo "4";
        }

        $obj->notificationStore('Customer Complain Added.', 'success');
        echo '1';
    } else {
        $obj->notificationStore('Complain Added Failed.', 'success');
        echo '0';
    }
}
?>
