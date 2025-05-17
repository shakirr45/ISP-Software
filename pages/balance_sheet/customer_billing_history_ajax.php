<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$monthArray = array(
    "1" => 'January',
    "2" => 'February',
    "3" => 'March',
    "4" => 'April',
    "5" => 'May',
    "6" => 'June',
    "7" => 'July',
    "8" => 'August',
    "9" => 'September',
    "10" => 'October',
    "11" => 'November',
    "12" => 'December',
);

date_default_timezone_set('Asia/Dhaka');
$date_time = date('Y-m-d g:i:sA');
$date = date('Y-m-d');
$ip_add = $_SERVER['REMOTE_ADDR'];
$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

$whereCond = '';
$dateMonth = date("n");
$dateYear = date("Y");
$pre_dateMonth = $dateMonth - 1;
$pre_dateYear = $dateYear;

if (isset($_GET['dateMonth']) && isset($_GET['dateYear'])) {
    $dateMonth = intval($_GET['dateMonth']);
    $dateYear = intval($_GET['dateYear']);
}

if (!empty($_GET['zone'])) {
    $whereCond = " AND zone.zone_id = $_GET[zone]";
}


$billing_histories = $obj->rawSql("SELECT billing.*, 
agent.ag_name, agent.ip, agent.ag_office_address, agent.ag_mobile_no, agent.mb AS pacakge, 
zone.zone_name
FROM `customer_billing` AS billing
LEFT JOIN tbl_agent AS agent ON agent.ag_id  = billing.agid
LEFT JOIN tbl_zone as zone ON agent.zone = zone.zone_id
WHERE MONTH(`generate_at`) = '$dateMonth' AND YEAR(`generate_at`) = '$dateYear' $whereCond");


$customer_billing_history = [];

foreach ($billing_histories as $key => $billing_history) {
    $customer_billing_history[] = [
        "sl" => $key + 1,
        "bill_month" => $monthArray[$dateMonth] . " - " . $dateYear,
        "customer_name" => $billing_history["ag_name"] ?? 'N/A',
        "customer_id" => $billing_history["cusid"],
        "ip" => $billing_history["ip"] ?? 'N/A',
        "zone" => $billing_history["zone_name"] ?? 'N/A',
        "address" => empty($billing_history["ag_office_address"]) ? 'N/A' : $billing_history["ag_office_address"],
        "mobile_no" => $billing_history["ag_mobile_no"] ?? 'N/A',
        "package" => $billing_history["pacakge"] ?? 'N/A',
        "monthly_bill" => $billing_history["monthlybill"],
        "due" => $billing_history[$_GET["billingType"]],
    ];
}

// Return JSON response
echo json_encode([
    "data" => $customer_billing_history
]);
exit();
