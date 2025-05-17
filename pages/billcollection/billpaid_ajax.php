<?php
// session_start();
// require(realpath(__DIR__ . '/../../services/Model.php'));
// $obj = new Model();

// $where = [];
// $wherecond = '';
// $allAgentData = [];
// $total_bill = $totalpaid = $totaladvance  = $totalcustomer = 0;
// $i = 1;

// // Apply filters from AJAX request
// if (isset($_GET['zone']) && !empty($_GET['zone'])) {
//     $zone = $_GET['zone'];
//     $wherecond .= " AND zone = $zone";
// }
// if (isset($_GET['bid']) && !empty($_GET['bid'])) {
//     $bid = $_GET['bid'];
//     $wherecond .= " AND billing_person_id = $bid";
// }
// if (isset($_GET['status']) && $_GET['status'] !== '') {
//     $status = $_GET['status'];
//     if ($status == 2) {
//         $whereInId = $obj->rawSql('SELECT DISTINCT agid FROM `customer_billing` WHERE dueadvance < 0');
//         if (!empty($whereInId)) {
//             $agentIds = implode(',', array_column($whereInId, 'agid'));
//             if (!empty($agentIds)) {
//                 $wherecond .= " AND ag_id IN ($agentIds)";
//             }
//         }
//     }
// }


// // if (isset($_GET['datefrom']) && !empty($_GET['datefrom'])) {
// //     $dateFrom = date('d', strtotime($_GET['datefrom']));
// //     $wherecond .= " AND mikrotik_disconnect = $dateFrom";
// // }


// // Apply search filter (if search is provided)
// $search = $_GET['search']['value'] ?? '';
// if (!empty($search)) {
//     $wherecond .= " AND (ag_name LIKE '%$search%' OR  cus_id LIKE '%$search%' OR  ip LIKE '%$search%' OR  mb LIKE '%$search%' OR  ag_mobile_no LIKE '%$search%' )";
// }
// // var_dump($where);

// // Pagination (start and length)
// $start = $_GET['start'] ?? 0;
// $length = $_GET['length'] ?? 10;

// // Fetch filtered data with pagination
// $allData = $obj->rawSql("SELECT vw_agent.*, customer_billing.dueadvance,tbl_zone.zone_name  FROM vw_agent left join customer_billing on customer_billing.agid = vw_agent.ag_id left join tbl_zone on tbl_zone.zone_id = vw_agent.zone WHERE deleted_at is NULL AND dueadvance  <= 0 AND totalgenerate > 0 AND ag_status = 1 $wherecond ORDER BY ag_id DESC LIMIT $length OFFSET $start;");

// $totalData = count($obj->rawSql("SELECT vw_agent.*, customer_billing.dueadvance  FROM vw_agent left join customer_billing on customer_billing.agid = vw_agent.ag_id WHERE deleted_at is NULL AND dueadvance  <= 0 AND totalgenerate > 0 AND ag_status = 1 $wherecond ORDER BY ag_id DESC")); // Total without filters
// $totalFiltered = count($obj->rawSql("SELECT vw_agent.*, customer_billing.dueadvance  FROM vw_agent left join customer_billing on customer_billing.agid = vw_agent.ag_id WHERE deleted_at is NULL AND dueadvance <= 0  AND totalgenerate > 0 AND ag_status = 1 $wherecond ORDER BY ag_id DESC")); // Total with applied filters

// foreach ($allData as $customer) {
//     $total_bill += $customer['taka'];
//     // $totalconnectionFee += $customer['connect_charge'];
//     $totalcustomer += 1;

//     $bp = $obj->getSingleData('_createuser', ['where' => ['UserId', '=', @$customer['billing_person_id']]]);
//     $customer['billingperson'] = @$bp['FullName'];
//     $customer['sl'] = $i++;

//     $cmpaid = $obj->rawSqlSingle('SELECT SUM(acc_amount) as paid, entry_date, _createuser.FullName  FROM `tbl_account`  left join _createuser on _createuser.UserId = tbl_account.entry_by WHERE acc_type=3 AND agent_id=' . $customer['ag_id'] . ' AND (MONTH(entry_date)=MONTH(CURRENT_DATE) AND YEAR(entry_date)=YEAR(CURRENT_DATE) )');

//     $customer['cmpaid'] = isset($cmpaid['paid']) ? $cmpaid['paid'] : 0;
//     $totalpaid += isset($cmpaid['paid']) ? $cmpaid['paid'] : 0;
//     $customer['cmpaiddate'] = isset($cmpaid['entry_date']) ? $cmpaid['entry_date'] : '-';
//     $customer['collectedby'] = isset($cmpaid['FullName']) ? $cmpaid['FullName'] : '-';
//     $customer['dueadvance'] = abs($customer['dueadvance']);
//     $totaladvance += abs($customer['dueadvance']);

//     $customer['mikrotik_disconnect'] = date('d-m-Y', strtotime($customer['mikrotik_disconnect'] . '-' . date('m-Y')));
//     $allAgentData[] = $customer;
// }

// // Return JSON response for DataTable
// echo json_encode([
//     "draw" => intval($_GET['draw']), // Draw counter from DataTable
//     "recordsTotal" => $totalData,    // Total records in database (without filters)
//     "recordsFiltered" => $totalFiltered, // Total records after filtering
//     "data" => $allAgentData,          // The actual data
//     "totalbill" => $total_bill,          // The actual data
//     "totalpaid" => $totalpaid,          // The actual data
//     "totaladvance" => $totaladvance,         // The actual data
//     // "whereInId" => $whereInId          // The actual data
// ]);


// exit();

session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$where = [];
$wherecond = '';
$allAgentData = [];
$total_bill = $totalpaid = $totaladvance  = $totalcustomer = 0;
$i = 1;

// Apply filters from AJAX request
if (isset($_GET['zone']) && !empty($_GET['zone'])) {
    $zone = $_GET['zone'];
    $wherecond .= " AND vw_agent.zone = $zone";
}
if (isset($_GET['bid']) && !empty($_GET['bid'])) {
    $bid = $_GET['bid'];
    $wherecond .= " AND vw_agent.billing_person_id = $bid";
}
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status = $_GET['status'];
    if ($status == 2) {
        $whereInId = $obj->rawSql('SELECT DISTINCT agid FROM `customer_billing` WHERE dueadvance < 0');
        if (!empty($whereInId)) {
            $agentIds = implode(',', array_column($whereInId, 'agid'));
            if (!empty($agentIds)) {
                $wherecond .= " AND vw_agent.ag_id IN ($agentIds)";
            }
        }
    }
}


// if (isset($_GET['datefrom']) && !empty($_GET['datefrom'])) {
//     $dateFrom = date('d', strtotime($_GET['datefrom']));
//     $wherecond .= " AND mikrotik_disconnect = $dateFrom";
// }
$order_column = $_GET['order'][0]['column'] ?? '';  // Column index
$order_dir = $_GET['order'][0]['dir'] ?? '';  // Order direction (asc or desc)
$columns = [
    'ag_id',
    'cus_id',
    'ip',
    'ag_name',
    'ag_office_address',
    'ag_mobile_no',
    'mb',
    'taka',
    'dueadvance',
    'mikrotik_disconnect',
    'cmpaid',
    'cmpaiddate',
    'collectedby',
    'zone_name',
    'FullName',
];

if (isset($order_column) && isset($columns[$order_column])) {
    if ($order_column == "12" || $order_column == "14") {
        // If the column is FullName, order by created_by (or the corresponding column)
        $order_by = "vw_agent.billing_person_id " . $order_dir;
    } elseif ($order_column == "13") {
        // If the column is FullName, order by created_by (or the corresponding column)
        $order_by = "vw_agent.zone " . $order_dir;
    } elseif ($order_column == "8") {
        // If the column is FullName, order by created_by (or the corresponding column)
        $order_by = "customer_billing.dueadvance " . $order_dir;
    } else {
        // Else use the valid column name from the array
        $order_by = "vw_agent." . $columns[$order_column] . ' ' . $order_dir;
    }
} else {
    // Default order by id descending
    $order_by = "vw_agent.ag_id DESC";
}


// Apply search filter (if search is provided)
$search = $_GET['search']['value'] ?? '';
if (!empty($search)) {
    $wherecond .= " AND (
    vw_agent.ag_name LIKE '%$search%' OR  vw_agent.cus_id LIKE '%$search%' OR  vw_agent.ip LIKE '%$search%' OR  vw_agent.mb LIKE '%$search%' OR  vw_agent.ag_mobile_no LIKE '%$search%' OR vw_agent.ag_office_address LIKE '%$search%' OR vw_agent.taka LIKE '%$search%' OR customer_billing.dueadvance LIKE '%$search%' OR tbl_zone.zone_name LIKE '%$search%' OR vw_agent.mikrotik_disconnect LIKE '%$search%' OR _createuser.FullName LIKE '%$search%'
    )";
}
// var_dump($where);

// Pagination (start and length)
$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;

// Fetch filtered data with pagination
$allData = $obj->rawSql("SELECT 
    vw_agent.*, 
    customer_billing.dueadvance, 
    tbl_zone.zone_name,
    SUM(tbl_account.acc_amount) AS cmpaid, 
    MAX(tbl_account.entry_date) AS cmpaiddate, 
    _createuser.FullName AS billingperson
FROM vw_agent 
LEFT JOIN customer_billing ON customer_billing.agid = vw_agent.ag_id 
LEFT JOIN tbl_zone ON tbl_zone.zone_id = vw_agent.zone
LEFT JOIN tbl_account ON tbl_account.agent_id = vw_agent.ag_id 
    AND tbl_account.acc_type = 3 
    AND MONTH(tbl_account.entry_date) = MONTH(CURRENT_DATE) 
    AND YEAR(tbl_account.entry_date) = YEAR(CURRENT_DATE)
LEFT JOIN _createuser ON _createuser.UserId = vw_agent.billing_person_id
WHERE 
    vw_agent.deleted_at IS NULL 
    AND customer_billing.dueadvance <= 0 
    AND customer_billing.totalgenerate > 0 
    AND vw_agent.ag_status = 1 
    -- অন্যান্য ফিল্টার 
    $wherecond
GROUP BY 
    vw_agent.ag_id, customer_billing.dueadvance, tbl_zone.zone_name, _createuser.FullName
ORDER BY 
    $order_by
LIMIT $length OFFSET $start;
");

$totalData = count($obj->rawSql("SELECT vw_agent.*, customer_billing.dueadvance  FROM vw_agent left join customer_billing on customer_billing.agid = vw_agent.ag_id WHERE deleted_at is NULL AND dueadvance  <= 0 AND totalgenerate > 0 AND ag_status = 1  ")); // Total without filters
$totalFiltered = count($obj->rawSql("SELECT vw_agent.*, customer_billing.dueadvance, 
    tbl_zone.zone_name,
    SUM(tbl_account.acc_amount) AS cmpaid, 
    MAX(tbl_account.entry_date) AS cmpaiddate, 
    _createuser.FullName AS billingperson
FROM vw_agent 
LEFT JOIN customer_billing ON customer_billing.agid = vw_agent.ag_id 
LEFT JOIN tbl_zone ON tbl_zone.zone_id = vw_agent.zone
LEFT JOIN tbl_account ON tbl_account.agent_id = vw_agent.ag_id 
    AND tbl_account.acc_type = 3 
    AND MONTH(tbl_account.entry_date) = MONTH(CURRENT_DATE) 
    AND YEAR(tbl_account.entry_date) = YEAR(CURRENT_DATE)
LEFT JOIN _createuser ON _createuser.UserId = vw_agent.billing_person_id
WHERE 
    vw_agent.deleted_at IS NULL 
    AND customer_billing.dueadvance <= 0 
    AND customer_billing.totalgenerate > 0 
    AND vw_agent.ag_status = 1 
    -- অন্যান্য ফিল্টার 
    $wherecond
GROUP BY 
    vw_agent.ag_id, customer_billing.dueadvance, tbl_zone.zone_name, _createuser.FullName
ORDER BY 
    $order_by")); // Total with applied filters

foreach ($allData as $customer) {
    $total_bill += $customer['taka'];
    // $totalconnectionFee += $customer['connect_charge'];
    $totalcustomer += 1;
    $customer['sl'] = $i++;

    $cmpaid = $obj->rawSqlSingle('SELECT SUM(acc_amount) as paid, entry_date, _createuser.FullName  FROM `tbl_account`  left join _createuser on _createuser.UserId = tbl_account.entry_by WHERE acc_type=3 AND agent_id=' . $customer['ag_id'] . ' AND (MONTH(entry_date)=MONTH(CURRENT_DATE) AND YEAR(entry_date)=YEAR(CURRENT_DATE) )');

    $customer['cmpaid'] = isset($cmpaid['paid']) ? $cmpaid['paid'] : 0;
    $totalpaid += isset($cmpaid['paid']) ? $cmpaid['paid'] : 0;
    $customer['cmpaiddate'] = isset($cmpaid['entry_date']) ? $cmpaid['entry_date'] : '-';
    $customer['collectedby'] = isset($cmpaid['FullName']) ? $cmpaid['FullName'] : '-';
    $customer['dueadvance'] = abs($customer['dueadvance']);
    $totaladvance += abs($customer['dueadvance']);

    $customer['mikrotik_disconnect'] = date('d-m-Y', strtotime($customer['mikrotik_disconnect'] . '-' . date('m-Y')));
    $allAgentData[] = $customer;
}

// Return JSON response for DataTable
echo json_encode([
    "draw" => intval($_GET['draw']), // Draw counter from DataTable
    "recordsTotal" => $totalData,    // Total records in database (without filters)
    "recordsFiltered" => $totalFiltered, // Total records after filtering
    "data" => $allAgentData,          // The actual data
    "totalbill" => $total_bill,          // The actual data
    "totalpaid" => $totalpaid,          // The actual data
    "totaladvance" => $totaladvance,         // The actual data
    // "whereInId" => $whereInId          // The actual data
]);


exit();

