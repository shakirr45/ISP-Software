<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$wherecond = '';
$allAgentData = [];
$i = 1;

// Apply filters from AJAX request
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category = $_GET['category'];
    $wherecond .= " AND complain_type = $category";
}
if (isset($_GET['priority']) && !empty($_GET['priority'])) {
    $priority = $_GET['priority'];
    $wherecond .= " AND priority = $priority";
}
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status = $_GET['status'];
    $wherecond .= " AND status = $status";
}
if (isset($_GET['customer']) && !empty($_GET['customer'])) {
    $customer = $_GET['customer'];
    $wherecond .= " AND customer_id = $customer";
}
if (isset($_GET['assign']) && $_GET['assign'] !== '') {
    $assign = $_GET['assign'];
    $wherecond .= " AND solve_by = $assign";
}
if (isset($_GET['datefrom']) && isset($_GET['dateto']) && !empty($_GET['datefrom']) && !empty($_GET['dateto'])) {
    $dateFrom = date('Y-m-d', strtotime($_GET['datefrom']));
    $dateto = date('Y-m-d', strtotime($_GET['dateto']));
    $wherecond .= " AND entry_date  BETWEEN '$dateFrom' AND '$dateto'";
}

// Apply search filter (if search is provided)
$search = $_GET['search']['value'] ?? '';
if (!empty($search)) {
    $wherecond .= " AND (note LIKE '%$search%' OR  details LIKE '%$search%' )";
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
    WHERE complain_type = $_GET[individual_complian_id] AND c.deleted_at IS NULL $wherecond
    ORDER BY c.id DESC 
    LIMIT $length OFFSET $start;
";

$allData = $obj->rawSql($sql);

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
        'priority_name' => $item['priority'],
        'status_name' => $item['status'],
        'solve_by' => $item['solve_by'] ?? 'N/A',
        'solve_date' => $item['solve_date'] ?? 'N/A',
        'details' => $item['details'] ?? 'N/A',
    ];
}

// Return or use $allAgentData as required
echo json_encode([
    "draw" => intval($_GET['draw']), // DataTable draw counter
    "recordsTotal" => $obj->rawSql("SELECT COUNT(id) FROM tbl_complains WHERE complain_type = $_GET[individual_complian_id] AND deleted_at IS NULL $wherecond")[0]["COUNT(id)"],
    "recordsFiltered" => $obj->rawSql("SELECT COUNT(id) FROM tbl_complains WHERE complain_type = $_GET[individual_complian_id] AND deleted_at IS NULL $wherecond")[0]["COUNT(id)"],
    "data" => $allAgentData
]);

exit();
