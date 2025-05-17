<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$where = [];
$wherecond = '';
$allAgentData = [];
$total  = $totalcustomer = 0;
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

if (isset($_GET['monthyear']) && !empty($_GET['monthyear'])) {
    $dateFrom = $_GET['monthyear'];
    $firstday = date('Y-m-d', strtotime($_GET['monthyear'] . "-01"));
    $lastday = date('Y-m-d', strtotime($_GET['monthyear'] . "-" . date('t', strtotime($_GET['monthyear'] . '-01'))));
    $wherecond .= " AND (date BETWEEN '$firstday' AND '$lastday')";
}

// Apply search filter (if search is provided)
$search = $_GET['search']['value'] ?? '';
if (!empty($search)) {
    $wherecond .= " AND (ag_name LIKE '%$search%' OR  cus_id LIKE '%$search%' OR  ip LIKE '%$search%' OR  mb LIKE '%$search%' OR  ag_mobile_no LIKE '%$search%' )";
}
// var_dump($where);

// Pagination (start and length)
$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;

// Fetch filtered data with pagination
$allData = $obj->rawSql("SELECT vw_agent.*, customer_billing.dueadvance,tbl_zone.zone_name , customer_billing.monthlybill, customer_billing.generate_at   FROM vw_agent left join customer_billing on customer_billing.agid = vw_agent.ag_id left join tbl_zone on tbl_zone.zone_id = vw_agent.zone WHERE customer_billing.dueadvance > 0 $wherecond ORDER BY ag_id DESC");

$totalData = count($obj->rawSql("SELECT vw_agent.*, customer_billing.dueadvance, customer_billing.monthlybill, customer_billing.generate_at  FROM vw_agent left join customer_billing on customer_billing.agid = vw_agent.ag_id WHERE customer_billing.dueadvance > 0 $wherecond ORDER BY ag_id DESC"));

$totalFiltered = count($obj->rawSql("SELECT vw_agent.*, customer_billing.dueadvance, customer_billing.monthlybill, customer_billing.generate_at   FROM vw_agent left join customer_billing on customer_billing.agid = vw_agent.ag_id WHERE customer_billing.dueadvance > 0 $wherecond ORDER BY ag_id DESC"));

foreach ($allData as $customer) {
    $total += $customer['dueadvance'];
    $totalcustomer += 1;

    $bp = $obj->getSingleData('_createuser', ['where' => ['UserId', '=', @$customer['billing_person_id']]]);
    $customer['billingperson'] = @$bp['FullName'];
    $customer['sl'] = $i++;
    $allAgentData[] = $customer;
}

// Return JSON response for DataTable
echo json_encode([
    "draw" => intval($_GET['draw']), // Draw counter from DataTable
    "recordsTotal" => $totalData,    // Total records in database (without filters)
    "recordsFiltered" => $totalFiltered, // Total records after filtering
    "data" => $allAgentData,          // The actual data
    "totalbill" => $total,          // The actual data
]);
exit();
