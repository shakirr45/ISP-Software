<?php
session_start();
include '../services/Model.php';
$obj = new Model();

$wherecond = '';
$allAgentData = [];
$total_bill = $totalconnectionFee = $totalcustomer = 0;
$i = 1;

// Apply filters from AJAX request
if (isset($_GET['zone']) && !empty($_GET['zone'])) {
    $zone = intval($_GET['zone']); // Sanitize input
    $wherecond .= " AND zone = $zone";
}
if (isset($_GET['bid']) && !empty($_GET['bid'])) {
    $bid = intval($_GET['bid']); // Sanitize input
    $wherecond .= " AND billing_person_id = $bid";
}
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status = intval($_GET['status']); // Sanitize input
    $wherecond .= " AND bill_status = $status";
}
if (isset($_GET['datefrom']) && isset($_GET['dateto']) && !empty($_GET['datefrom']) && !empty($_GET['dateto'])) {
    $dateFrom = date('d', strtotime($_GET['datefrom']));
    $dateTo = date('d', strtotime($_GET['dateto']));
    $wherecond .= " AND ((mikrotik_disconnect >= $dateFrom) AND (mikrotik_disconnect <= $dateTo))";
}

// Apply search filter (if search is provided)
// $search = $obj->escapeString($_GET['search']['value'] ?? ''); // Escape search input
// if (!empty($search)) {
//     $wherecond .= " AND (ag_name LIKE '%$search%' OR cus_id LIKE '%$search%' OR ip LIKE '%$search%' OR mb LIKE '%$search%' OR ag_mobile_no LIKE '%$search%')";
// }

// Pagination (start and length)
$start = intval($_GET['start'] ?? 0); // Default to 0
$length = intval($_GET['length'] ?? 10); // Default to 10

// Fetch filtered data with pagination
$allData = $obj->rawSql("
    SELECT vw_agent.*, customer_billing.dueadvance, tbl_zone.zone_name 
    FROM vw_agent 
    LEFT JOIN customer_billing ON customer_billing.agid = vw_agent.ag_id 
    LEFT JOIN tbl_zone ON tbl_zone.zone_id = vw_agent.zone 
    WHERE deleted_at IS NULL 
    AND dueadvance > 0 
    AND ag_status = 1 
    $wherecond 
    ORDER BY ag_id DESC 
    LIMIT $length OFFSET $start
;");

// Get total records without filters
$totalDataQuery = $obj->rawSql("
    SELECT COUNT(*) as total 
    FROM vw_agent 
    LEFT JOIN customer_billing ON customer_billing.agid = vw_agent.ag_id 
    WHERE deleted_at IS NULL 
    AND dueadvance > 0 
    AND ag_status = 1
;");
$totalData = $totalDataQuery[0]['total'] ?? 0;

// Get total records with applied filters
$totalFilteredQuery = $obj->rawSql("
    SELECT COUNT(*) as total 
    FROM vw_agent 
    LEFT JOIN customer_billing ON customer_billing.agid = vw_agent.ag_id 
    WHERE deleted_at IS NULL 
    AND dueadvance > 0 
    AND ag_status = 1 
    $wherecond
;");
$totalFiltered = $totalFilteredQuery[0]['total'] ?? 0;

// Process fetched data
foreach ($allData as $customer) {
    $total_bill += $customer['taka'];
    $totalcustomer += 1;

    // Get billing person information
    $bp = $obj->getSingleData('_createuser', ['where' => ['UserId', '=', @$customer['billing_person_id']]]);
    $customer['billingperson'] = @$bp['FullName'];

    // Add serial number and formatted date
    $customer['sl'] = $i++;
    $customer['mikrotik_disconnect'] = date('d-m-Y', strtotime($customer['mikrotik_disconnect'] . '-' . date('m-Y')));
    $allAgentData[] = $customer;
}

// Return JSON response for DataTable
echo json_encode(
     // Draw counter from DataTable
     // Total records in database (without filters)
     // Total records after filtering
    $allAgentData
);
exit();
