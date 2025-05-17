<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$where = [];
$wherecond = '';
$allAgentData = [];
$total_bill = $totalconnectionFee = $totalcustomer = 0;
$i = 1;

// Apply filters from AJAX request
/* if (isset($_GET['zone']) && !empty($_GET['zone'])) {
    $zone = $_GET['zone'];
    $wherecond .= " AND zone = $zone";
} */
/* if (isset($_GET['bid']) && !empty($_GET['bid'])) {
    $bid = $_GET['bid'];
    $wherecond .= " AND billing_person_id = $bid";
} */
/* if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status = $_GET['status'];
    $wherecond .= " AND ag_status = $status";
} */
/* if (isset($_GET['datefrom']) && isset($_GET['dateto']) && !empty($_GET['datefrom']) && !empty($_GET['dateto'])) {
    $dateFrom = date('Y-m-d', strtotime($_GET['datefrom']));
    $dateto = date('Y-m-d', strtotime($_GET['dateto']));
    $wherecond .= " AND entry_date  BETWEEN '$dateFrom' AND '$dateto'";
} */

// Apply search filter (if search is provided)
/* $search = $_GET['search']['value'] ?? '';
if (!empty($search)) {
    $wherecond .= " AND (ag_name LIKE '%$search%' OR  cus_id LIKE '%$search%' OR  ip LIKE '%$search%' OR  mb LIKE '%$search%' OR  zone_name LIKE '%$search%' )";
} */
// var_dump($where);

// Pagination (start and length)


// Fetch filtered data with pagination
// $allData = $obj->rawSql("SELECT * FROM vw_agent WHERE deleted_at is NULL $wherecond ORDER BY ag_id DESC LIMIT $length OFFSET $start;");


// $totalData = count($obj->rawSql("SELECT * FROM vw_agent WHERE deleted_at is NULL $wherecond ORDER BY ag_id DESC")); // Total without filters
$totalFiltered = count($obj->rawSql("SELECT * FROM vw_agent WHERE deleted_at is NULL $wherecond ORDER BY ag_id DESC")); // Total with applied filters

$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;

$allData = $obj->rawSql("SELECT tbl_complains.*, tbl_employee.* 
                FROM tbl_complains
                LEFT JOIN tbl_employee ON tbl_complains.solve_by = tbl_employee.id
                $wherecond");

foreach ($allData as $complain) {
    $allAgentData[] = [
        'sl' => $i++,
        'details' => $complain['details'],
        'customer_id' => $complain['customer_id'],
        'complain_date' => $complain['complain_date'],
    ];
}

// Return JSON response for DataTable
echo json_encode([
    "data" => $allAgentData,
    'tempAllDAta' => $allAgentData
]);
exit();
?>