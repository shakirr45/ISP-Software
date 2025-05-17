<?php

include("dbconfig_robotisp.php");
include '../services/Model.php';
$obj = new Model();

// var_dump($obj->checkConnection($mikrotikget));
if ($_SERVER['REQUEST_METHOD']=='POST') {

    $mik_ip = $_POST['mik_ip'];

    $query = "SELECT * FROM mikrotik_user WHERE mik_ip = '$mik_ip'";
    $result_all = mysqli_query($con, $query);

    if (mysqli_num_rows($result_all) > 0) {

        $data = $obj->rawSql("SELECT ag_id FROM tbl_agent ORDER BY created_at DESC LIMIT 1");
        if (!$data) {
            $data[0]["ag_id"] = 0;
        }
        if (($data[0]["ag_id"] + 1) < 10) {
            $STD = "CUS000";
        } else if (($data[0]["ag_id"] + 1) < 100) {
            $STD = "CUS000";
        } else if (($data[0]["ag_id"] + 1) < 1000) {
            $STD = "CUS00";
        } else if (($data[0]["ag_id"] + 1) < 10000) {
            $STD = "CUS0";
        } else {
            $STD = "CUS";
        }
        $STD .= $data[0]["ag_id"] + 1;
        $userId = $_POST['user_id'];

        $fromInsert = [
            'cus_id' => $STD,
            'ag_name' => $_POST['ag_name'],
            'ip' => $_POST['ip'],
            'type' => 1,
            'mikrotik_disconnect' => $_POST['mikrotik_disconnect'],
            'taka' => $obj->convertBanglaToEnglishNumbers($_POST['taka']),
            'mb' => $_POST['mb'],
            'int_mb' => intval($_POST['mb']),
            'ag_status' => $_POST['ag_status'],
            'ag_mobile_no' => $obj->convertBanglaToEnglishNumbers($_POST['ag_mobile_no']),
            'regular_mobile' => $obj->convertBanglaToEnglishNumbers($_POST['regular_mobile']),
            'ag_office_address' => $_POST['address'],
            'ag_email' => $_POST['ag_email'],
            'national_id' => $_POST['national_id'],
            //  'nationalidphoto'=>$_POST[''],
            'gender' => $_POST['gender'],
            'onumac' => $_POST['onumac'],
            'fibercode' => $_POST['fibercode'],
            'connectiontype' => $_POST['connectiontype'],
            'agent_type' => $_POST['agent_type'],
            'billing_person_id' => $_POST['billing_person_id'],
            'entry_by' => $userId,
            'entry_date' => date('Y-m-d', strtotime('first day of this month')),
            'connection_date' => $_POST['connection_date'],
            'remark' => $_POST['remark'],
            'queue_password' => $_POST['queue_password'],
            'zone' => $_POST['zone'],
            'sub_zone' => $_POST['sub_zone'],
            'destination' => $_POST['destination'],
            'mikrotik_id' => $_POST['mikrotik_id']
        ];

        $lastinsert = $obj->insertData('tbl_agent', $fromInsert);
        $obj->insertData('customer_billing', ['agid' => $lastinsert, 'cusid' => $STD, 'monthlybill' => $obj->convertBanglaToEnglishNumbers($_POST['taka']), 'generate_at' => '2024-01-01']);

        if (isset($_POST['connect_charge']) && !empty($_POST['connect_charge'])) {
            $obj->insertData("tbl_account", [
                'cus_id' => $STD,
                'agent_id' => $lastinsert,
                'acc_amount' => $_POST['connect_charge'],
                'acc_type' => '4', //connection charge sent tbl_account
                'acc_description' => 'Connection Charge',
                'entry_by' => $userId,
                'entry_date' => date('Y-m-d'),
                'update_by' => $userId
            ]);
        }

        if (isset($_POST['effected']) && $_POST['effected']) {

            if (isset($_POST['runningpaid']) && $_POST['runningpaid'] > 0) {
                $obj->rawSql("SELECT function_bill_update(" . $lastinsert . ", 'billpay', " . $_POST['runningpaid'] . ", '$STD', '',$userId) AS function_bill_update");
            }
            $obj->rawSql("SELECT function_bill_update(" . $lastinsert . ", 'effectedUpdate', '', '', '','') AS function_bill_update");
        } else {
            $obj->insertData("tbl_account", [
                'cus_id' => $STD,
                'agent_id' => $lastinsert,
                'acc_type' => '5',
                'acc_amount' => $_POST['runningpaid'],
                'acc_description' => 'Opening and Running Month amount Payment',
                'entry_by' => $userId,
                'entry_date' => date('Y-m-d'),
                'update_by' => $userId
            ]);
        }

        // send sms to the customer
        if (isset($_POST["smssend"]) && $_POST["smssend"] == 'smssend') {
            $customer = $obj->rawSqlSingle("SELECT * FROM tbl_agent WHERE ag_id = $lastinsert");
            $mobile = "88" . strval($customer['ag_mobile_no']);
            $customerName = isset($customer['ag_name']) ? $customer['ag_name'] : NULL;
            $cusId = isset($customer['cus_id']) ? $customer['cus_id'] : NULL;
            $cusIp = isset($customer['ip']) ? $customer['ip'] : NULL;
            $cusPackage = isset($customer['mb']) ? $customer['mb'] : NULL;
            $cusbill = isset($customer['taka']) ? $customer['taka'] : NULL;

            $sms = $obj->sendsms($mobile, "Dear $customerName, ID: $cusId, Username:$cusIp, Package:$cusPackage, Monthly Bill:$cusbill ");
        }

        $obj->notificationStore("New Customer Add  Successfull", 'success');
        echo '1';
       
        exit;
    }else{
        echo "0";
    }
}
error_reporting(0);
$obj->notificationShow();
