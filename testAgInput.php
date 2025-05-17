<?php
$usermikrotik = 0;
// Edit 
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $customer = $obj->getSingleData("tbl_agent", ['where' => ['ag_id', '=', $token]]);
    if (isset($customer['mikrotik_id'])) {
        $usermikrotik = $customer['mikrotik_id'];
        $usersecret = $customer['ip'];
    }
}
// End edit
$mikrotikget = isset($_GET['mikrotik']) ? $_GET['mikrotik'] : $usermikrotik;
$mikrotikConnect = ($obj->checkConnection($mikrotikget)) ? true : false;
$activeMikrotik = $obj->getSingleData('mikrotik_user', ['where' => ['status', '=', '1']]);
$checkconenctM = false;
if ($activeMikrotik) {
    if ($mikrotikConnect) {
        $checkconenctM = true;
    }
} else {
    $checkconenctM = true;
}

// var_dump($obj->checkConnection($mikrotikget));
if (isset($_POST['submit'])) {

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
    ];

    if ($mikrotikConnect) {
        $fromInsert['mikrotik_id'] = $_POST['mikrotik_id'];
        $fromInsert['queue_password'] = $_POST['queue_password'];
    }
    if (isset($_POST['zone'])) {
        $fromInsert['zone'] = $_POST['zone'];
    }
    if (isset($_POST['sub_id'])) {
        $fromInsert['sub_zone'] = $_POST['sub_id'];
    }
    if (isset($_POST['destination'])) {
        $fromInsert['destination'] = $_POST['destination'];
    }

    $lastinsert = $obj->insertData('tbl_agent', $fromInsert);
    $obj->insertData('customer_billing', ['agid' => $lastinsert, 'cusid' => $STD, 'monthlybill' => $obj->convertBanglaToEnglishNumbers($_POST['taka']), 'generate_at' => '2024-01-01']);

    // if (isset($_POST['runningpaid']) && !empty($_POST['runningpaid'])) {
    //     $obj->insertData("tbl_account", [
    //         'cus_id' => $STD,
    //         'agent_id' => $lastinsert,
    //         'acc_type' => '5',
    //         'acc_amount' => $_POST['runningpaid'],
    //         'acc_description' => 'Opening and Running Month amount Payment',
    //         'entry_by' => $userId,
    //         'entry_date' => date('Y-m-d'),
    //         'update_by' => $userId
    //     ]);
    // }

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


    if ($mikrotikConnect) {
        $obj->createNewSecret($mikrotikget, $_POST['ip'], $_POST['queue_password'], $_POST['mb'], $_POST['ip'], $_POST['remark']);
        if (($_POST['ag_status'] == 0) || ($_POST['ag_status'] == 3)) {
            $obj->disableSingleSecret($mikrotikget, $_POST['ip']);
        }
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
    echo ' <script>window.location="?page=customer_view"; </script>';
    exit;
}


if (isset($_POST['update']) && isset($_POST['ag_id']) && !empty(isset($_POST['ag_id']))) {
    $fromUpdate = [
        // 'cus_id'=>$_POST[''],
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
        'zone' => $_POST['zone'],
        'sub_zone' => $_POST['sub_id'],
        'destination' => $_POST['destination'] ?? 0,
        'ag_email' => $_POST['ag_email'],
        'national_id' => $_POST['national_id'],
        //  'nationalidphoto'=>$_POST[''],
        'gender' => $_POST['gender'],
        'onumac' => $_POST['onumac'],
        'fibercode' => $_POST['fibercode'],
        'connectiontype' => $_POST['connectiontype'],
        'agent_type' => $_POST['agent_type'],
        //  'inactive_date'=>$_POST[''],
        'billing_person_id' => $_POST['billing_person_id'],
        'entry_by' => $userId,
        'entry_date' => date('Y-m-d', strtotime('first day of this month')),
        'connection_date' => $_POST['connection_date'],
        'remark' => $_POST['remark'],
    ];

    if ($mikrotikConnect) {

        $fromUpdate['queue_password'] = $_POST['queue_password'];

        $obj->updateExistingSecret($mikrotikget, $_POST['ip'], $_POST['queue_password'], $_POST['mb'], $_POST['remark']);
        if (($_POST['ag_status'] == 0) || ($_POST['ag_status'] == 3)) {
            $obj->disableSingleSecret($mikrotikget, $_POST['ip']);
        } else {
            $obj->enableSingleSecret($mikrotikget, $_POST['ip']);
        }
    }

    if (isset($_POST['change']) && $_POST['change']) {
        $obj->rawSql("SELECT function_bill_update(" . $_POST['ag_id'] . ", 'package', " . $_POST['taka'] . ", '', 'change','') AS function_bill_update");
    } else {
        $obj->rawSql("SELECT function_bill_update(" . $_POST['ag_id'] . ", 'package', " . $_POST['taka'] . ", '', '','') AS function_bill_update");
    }



    $obj->updateData('tbl_agent', $fromUpdate, ['ag_id' => $_POST['ag_id']]);

    $obj->notificationStore("Customer update  Successfull", 'success');
    echo ' <script>window.location="?page=customer_view"; </script>';
    exit;
}


// delete 
if ($obj->userWorkPermission('delete')) {
    if (isset($_GET['delete-token'])) {
        $token = isset($_GET['delete-token']) ? $_GET['delete-token'] : null;
        $deleted = true;
        $obj->updateData('tbl_agent', ['deleted_at' => date('Y-m-d H:i:m A')], ['ag_id' => $_GET['delete-token']]);
        //   $deleteCustomer = $obj->getSingleData("tbl_agent", [['ag_id','=',$token]]);
        //   if($deleteCustomer){
        //     $form_data=[
        //         'ag_id'=>$deleteCustomer['ag_id'],
        //         'cus_id'=>$deleteCustomer['cus_id'],
        //         'ip'=>$deleteCustomer['ip'],
        //         'type'=>$deleteCustomer['type'],
        //         'queue_password'=>$deleteCustomer['queue_password'],
        //         'ag_name'=>$deleteCustomer['ag_name'],
        //         'ag_mobile_no'=>$deleteCustomer['ag_mobile_no'],
        //         'ag_office_address'=>$deleteCustomer['ag_office_address'],
        //         'mikrotik_id'=>$deleteCustomer['mikrotik_id'],
        //         'mb'=>$deleteCustomer['mb'],
        //         'ag_status'=>$deleteCustomer['ag_status'],
        //         'int_mb'=>$deleteCustomer['int_mb'],
        //         'taka'=>$deleteCustomer['taka'],
        //         'connect_charge'=>$deleteCustomer['connect_charge'],
        //         'hold_money_status'=>$deleteCustomer['hold_money_status'],
        //         'inactive_date'=>$deleteCustomer['inactive_date'],
        //         'ag_email'=>$deleteCustomer['ag_email'],
        //         'regular_mobile'=>$deleteCustomer['regular_mobile'],
        //         'blood_group'=>$deleteCustomer['blood_group'],
        //         'occupation'=>$deleteCustomer['occupation'],
        //         'national_id'=>$deleteCustomer['national_id'],
        //         'mac_address'=>$deleteCustomer['mac_address'],
        //         'pay_status'=>$deleteCustomer['pay_status'],
        //         'due_status'=>$deleteCustomer['due_status'],
        //         'bill_status'=>$deleteCustomer['bill_status'],
        //         'payment_type'=>$deleteCustomer['payment_type'],
        //         'road'=>$deleteCustomer['road'],
        //         'house'=>$deleteCustomer['house'],
        //         'thana'=>$deleteCustomer['thana'],
        //         'mikrotik_disconnect'=>$deleteCustomer['mikrotik_disconnect'],
        //         'bill_date'=>$deleteCustomer['bill_date'],
        //         'bill_cat'=>$deleteCustomer['bill_cat'],
        //         'sms_sent'=>$deleteCustomer['sms_sent'],
        //         'billing_person_id'=>$deleteCustomer['billing_person_id'],
        //         'zone'=>$deleteCustomer['zone'],
        //         'entry_by'=>$deleteCustomer['entry_by'],
        //         'entry_date'=>$deleteCustomer['entry_date'],
        //         'connection_date'=>$deleteCustomer['connection_date'],
        //         'update_by'=>$deleteCustomer['update_by'],
        //         'last_update'=>$deleteCustomer['last_update'],
        //         'deleter_user_id'=>$userId,
        //     ];

        //     if($obj->insertData('delete_tbl_agent_log', $form_data)){
        //         $deleted = $obj->deleteData("tbl_agent", [['ag_id','=',$token]]);
        //     }
        //   }

        if ($deleted) {
            $obj->notificationStore('Customer is Deleted', 'success');
            echo '<script>window.location = "?page=customer_view";</script>';
        } else {
            $obj->notificationStore('Delete Failed', 'danger');
            echo '<script>window.location = "?page=customer_view";</script>';
        }
        die;
    }
}



// EDit page
if (isset($_GET['token'])) {
    $mikrotiksecret = ($checkconenctM) ? $obj->showSingleSecret($usermikrotik, "$usersecret") : false;
}


// EDit page





$obj->notificationShow();
