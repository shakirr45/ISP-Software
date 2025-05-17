<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();
$sms = 'Something is wrong';
if (isset($_GET['token'])) {
    $customer_id = $_GET['token'];

    // $customer = $obj->getSingleData('tbl_agent', ['ag_id', '=', $customer_id]);
    $customer = $obj->rawSqlSingle("SELECT * FROM tbl_agent WHERE ag_id = $customer_id");
    $mobile = "88" . strval($customer['ag_mobile_no']);
    $customerName = isset($customer['ag_name']) ? $customer['ag_name'] : NULL;
    $cusId = isset($customer['cus_id']) ? $customer['cus_id'] : NULL;
    $cusIp = isset($customer['ip']) ? $customer['ip'] : NULL;
    $cusPackage = isset($customer['mb']) ? $customer['mb'] : NULL;
    $cusbill = isset($customer['taka']) ? $customer['taka'] : NULL;

    $sms = $obj->sendsms($mobile, "Dear $customerName, here are your details: ID - $cusId, Username - $cusIp, Package - $cusPackage, Monthly Bill - $cusbill. Thank you for being with us.");
}

echo json_encode(['response' => $sms]);
