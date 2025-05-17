<?php
session_start();
include '../services/Model.php';
$obj = new Model();

$where = [];
$wherecond = '';
$allAgentData = [];
$total_bill = $totalconnectionFee = $totalcustomer = 0;
$i = 1;

// Apply filters from AJAX request
if (isset($_GET['zone']) && !empty($_GET['zone'])) {
    $zone = $_GET['zone'];
    $wherecond .= " AND zone = $zone";
}
if (isset($_GET['bid']) && !empty($_GET['bid'])) {
    $bid = $_GET['bid'];
    $wherecond .= " AND billing_person_id = $bid";
}
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status = $_GET['status'];
    $wherecond .= " AND ag_status = $status";
}
if (isset($_GET['datefrom']) && isset($_GET['dateto']) && !empty($_GET['datefrom']) && !empty($_GET['dateto'])) {
    $dateFrom = date('Y-m-d', strtotime($_GET['datefrom']));
    $dateto = date('Y-m-d', strtotime($_GET['dateto']));
    $wherecond .= " AND entry_date  BETWEEN '$dateFrom' AND '$dateto'";
}

// Apply search filter (if search is provided)
$search = $_GET['search']['value'] ?? '';
if (!empty($search)) {
    $wherecond .= " AND (ag_name LIKE '%$search%' OR  cus_id LIKE '%$search%' OR  ip LIKE '%$search%' OR  mb LIKE '%$search%' OR  zone_name LIKE '%$search%' )";
}
// var_dump($where);

// Pagination (start and length)
$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;

// Fetch filtered data with pagination
$allData = $obj->rawSql("SELECT *,tbl_zone.zone_name FROM vw_agent left join tbl_zone on tbl_zone.zone_id = vw_agent.zone WHERE deleted_at is NULL $wherecond ORDER BY ag_id DESC LIMIT $length OFFSET $start;");

$totalData = count($obj->rawSql("SELECT * FROM vw_agent WHERE deleted_at is NULL $wherecond ORDER BY ag_id DESC")); // Total without filters
$totalFiltered = count($obj->rawSql("SELECT * FROM vw_agent WHERE deleted_at is NULL $wherecond ORDER BY ag_id DESC")); // Total with applied filters

foreach ($allData as $customer) {
    $total_bill += $customer['taka'];
    // $totalconnectionFee += $customer['connect_charge'];
    $totalcustomer += 1;

    $bp = $obj->getSingleData('_createuser', [['UserId', '=', @$customer['billing_person_id']]]);
    $customer['billingperson'] = @$bp['FullName'];
    $customer['sl'] = $i++;
    $allAgentData[] = $customer;
}

// Return JSON response for DataTable
echo json_encode(
    $allAgentData
);
exit();
