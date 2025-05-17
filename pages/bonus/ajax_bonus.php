<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$i = 1;
$where = [];
$wherecond = '';
$allBonusData = [];
$totalBonusAmount = 0;

$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;
$searchValue = $_GET['search']['value'] ?? ''; // Search value

// Date filter
if (!empty($_GET['datefrom']) && !empty($_GET['dateto'])) {
    $dateFrom = date('Y-m-d', strtotime($_GET['datefrom']));
    $dateto = date('Y-m-d', strtotime($_GET['dateto']));
    $wherecond .= " WHERE generate_at BETWEEN '$dateFrom' AND '$dateto'";
}
$order_column = $_GET['order'][0]['column'] ?? '';  // Column index
$order_dir = $_GET['order'][0]['dir'] ?? '';  // Order direction (asc or desc)

$columns = [
    'id',
    'generate_at',
    'ag_name',
    'ag_office_address',
    'ag_mobile_no',
    'ag_email',
    'totaldiscount',
];
if (!empty($order_column) && isset($columns[$order_column])) {
    if ($order_column == "2" || $order_column == "3" || $order_column == "4" || $order_column == "5") {
        // If the column is FullName, order by created_by (or the corresponding column)
        $order_by = "customer_billing.agid " . $order_dir;
    } else {
        // Else use the valid column name from the array
        $order_by = "customer_billing." . $columns[$order_column] . ' ' . $order_dir;
    }
} else {
    // Default order by id descending
    $order_by = "customer_billing.id DESC";
}
// if (!empty($order_column)) {
//     $order_column = str_replace('bonus.', '', $order_column);
//     $wherecond .= " ORDER BY $order_column $order_dir";
// }

// Search filter
if (!empty($searchValue)) {
    $searchCondition = " (ag_name LIKE '%$searchValue%' OR ag_office_address LIKE '%$searchValue%' OR ag_mobile_no LIKE '%$searchValue%' OR ag_email LIKE '%$searchValue%')";
    $wherecond .= empty($wherecond) ? " WHERE $searchCondition" : " AND $searchCondition";
}

// Total records without filtering
$totalRecords = count($obj->rawSql("SELECT * FROM customer_billing LEFT JOIN tbl_agent ON customer_billing.agid = tbl_agent.ag_id GROUP BY customer_billing.agid"));

// Total records after filtering
$totalFiltered = count($obj->rawSql("SELECT * FROM customer_billing LEFT JOIN tbl_agent ON customer_billing.agid = tbl_agent.ag_id $wherecond GROUP BY customer_billing.agid"));

// Fetch the data with pagination
$allData = $obj->rawSql("SELECT * FROM customer_billing LEFT JOIN tbl_agent ON customer_billing.agid = tbl_agent.ag_id $wherecond GROUP BY customer_billing.agid ORDER BY $order_by LIMIT $start, $length");

// Calculate total bonus amount and prepare data
foreach ($allData as $bonus) {
    $ag_id = $bonus['ag_id'];
    $totalBonusAmount += $bonus['totaldiscount'];
    $bonus['sl'] = $i++;
    $allBonusData[] = $bonus;
}

// Return JSON response for DataTable
echo json_encode([
    "draw" => intval($_GET['draw']), // Pass the draw count from DataTable
    "recordsTotal" => $totalRecords, // Total unfiltered records
    "recordsFiltered" => $totalFiltered, // Total filtered records
    "data" => $allBonusData, // The actual data
    "totalBonusAmount" => $totalBonusAmount, // Total bonus amount
]);
exit();
