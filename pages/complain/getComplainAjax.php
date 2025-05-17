<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$wherecond = '';
$allAgentData = [];
$i = 1;

// Apply filters from AJAX request
if (!empty($_GET['category'])) {
    $category = $_GET['category'];
    $wherecond .= " AND c.complain_type = '$category'";
}
if (!empty($_GET['priority'])) {
    $priority = $_GET['priority'];
    $wherecond .= " AND c.priority = '$priority'";
}
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status = $_GET['status'];
    $wherecond .= " AND c.status = '$status'";
}
if (!empty($_GET['customer'])) {
    $customer = $_GET['customer'];
    $wherecond .= " AND c.customer_id = '$customer'";
}
if (isset($_GET['assign']) && $_GET['assign'] !== '') {
    $assign = $_GET['assign'];
    $wherecond .= " AND c.solve_by = '$assign'";
}
if (!empty($_GET['datefrom']) && !empty($_GET['dateto'])) {
    $dateFrom = date('Y-m-d', strtotime($_GET['datefrom']));
    $dateto = date('Y-m-d', strtotime($_GET['dateto']));
    $wherecond .= " AND DATE(c.entry_date) BETWEEN '$dateFrom' AND '$dateto'";
}

$order_column = $_GET['order'][0]['column'] ?? '';  // Column index
$order_dir = $_GET['order'][0]['dir'] ?? '';  // Order direction (asc or desc)
$columns = [
    'id',
    'customer_name',
    'customer_ip',
    'address',
    'phone',
    'complain_name',
    'details',
    'complain_date',
    'solve_by',
    'solve_date',
    'status',
    'priority',
];
// Construct the ORDER BY clause dynamically
if (isset($order_column) && isset($columns[$order_column])) {
    if ($order_column == "2" || $order_column == "3" || $order_column == "4" || $order_column == "1") {
        // If the column is FullName, order by created_by (or the corresponding column)
        $order_by = "c.customer_id " . $order_dir;
    } elseif ($order_column == "5") {
        $order_by = "c.complain_type " . $order_dir;
    } elseif ($order_column == "8") {
        $order_by = "c.solve_by " . $order_dir;
    } else {
        // Else use the valid column name from the array
        $order_by = "c." . $columns[$order_column] . ' ' . $order_dir;
    }
} else {
    // Default order by id descending
    $order_by = "c.id DESC";
}

// Apply search filter (if search is provided)
$search = $_GET['search']['value'] ?? '';
if (!empty($search)) {
    $wherecond .= " AND (
        a.ag_name LIKE '%$search%' OR 
        a.ip LIKE '%$search%' OR
        a.ag_office_address LIKE '%$search%' OR
        a.ag_mobile_no LIKE '%$search%' OR
        e.employee_name LIKE '%$search%' OR
        t.template LIKE '%$search%' OR
        c.details LIKE '%$search%' OR
        c.complain_date LIKE '%$search%' OR
        c.solve_date LIKE '%$search%'
    )";
}

// Pagination (start and length)
$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;

$sql = "
    SELECT 
        c.*, 
        a.ag_name AS customer_name, 
        a.ip AS customer_ip, 
        a.ag_office_address AS address, 
        a.ag_mobile_no AS phone, 
        e.employee_name AS solve_by, 
        t.template AS complain_name
    FROM tbl_complains c
    LEFT JOIN vw_agent a ON c.customer_id = a.ag_id
    LEFT JOIN tbl_employee e ON c.solve_by = e.id
    LEFT JOIN tbl_complain_templates t ON c.complain_type = t.id
    WHERE c.deleted_at IS NULL $wherecond
    ORDER BY $order_by
    LIMIT $length OFFSET $start;
";

$allData = $obj->rawSql($sql);

// Fetch filtered data with pagination
// $allData = $obj->rawSql("SELECT * FROM tbl_complains WHERE deleted_at is NULL $wherecond ORDER BY id DESC LIMIT $length OFFSET $start;");

$totalData = count($obj->rawSql("SELECT c.* FROM tbl_complains c 
LEFT JOIN vw_agent a ON c.customer_id = a.ag_id
LEFT JOIN tbl_employee e ON c.solve_by = e.id
LEFT JOIN tbl_complain_templates t ON c.complain_type = t.id
WHERE c.deleted_at is NULL $wherecond ORDER BY $order_by")); // Total without filters
$totalFiltered = count($obj->rawSql("SELECT c.* FROM tbl_complains c
LEFT JOIN vw_agent a ON c.customer_id = a.ag_id
LEFT JOIN tbl_employee e ON c.solve_by = e.id
LEFT JOIN tbl_complain_templates t ON c.complain_type = t.id
 WHERE c.deleted_at is NULL $wherecond ORDER BY $order_by")); // Total with applied filters

foreach ($allData as $item) {


    $allAgentData[] = [
        'sl' => $i++,
        'customer_name' => $item['customer_name'] ?? 'N/A',
        'customer_ip' => $item['customer_ip'] ?? 'N/A',
        'address' => $item['address'] ?? 'N/A',
        'phone' => $item['phone'] ?? 'N/A',
        'id' => $item['id'] ?? 'N/A',
        'note' => $item['note'] ?? 'N/A',
        'complain_date' => $item['complain_date'] ?? 'N/A',
        'complain_name' => $item['complain_name'] ?? 'N/A',
        'priority' => $item['priority'],
        'status' => $item['status'],
        'solve_by' => $item['solve_by'] ?? 'N/A',
        'solve_date' => $item['solve_date'] ?? 'N/A',
        'details' => $item['details'] ?? 'N/A',
    ];
}

// Return or use $allAgentData as required
echo json_encode([
    "data" => $allAgentData,
    "recordsTotal" => $totalData,
    "recordsFiltered" => $totalFiltered,
]);

exit();
