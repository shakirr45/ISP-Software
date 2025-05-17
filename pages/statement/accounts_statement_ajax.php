<?php
session_start();
header('Content-Type: application/json'); // Ensure the response is JSON
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

// Initialize query parameters

$total = 0;
$balance = 0;
$debit_total = 0;
$credit_total = 0;

// Get filter values from the request
$zone = isset($_GET['zone']) ? $_GET['zone'] : '';
$billing_person = isset($_GET['bid']) ? $_GET['bid'] : '';
$collection_person = isset($_GET['cid']) ? $_GET['cid'] : '';
$dateft = isset($_GET['date']) ? $_GET['date'] : '';
// $dto = isset($_GET['dto']) ? $_GET['dto'] : '';

$wherecond = '';
if ($zone != '') {
    $wherecond .= " AND tbl_agent.zone = '$zone'";
}
if ($billing_person != '') {
    $wherecond .= " AND tbl_agent.billing_person_id = '$billing_person'";
}


if ($collection_person != '') {
    $wherecond .= " AND tbl_account.entry_by = '$collection_person'";
}
if ($dateft != '') {
    $datearay = explode('/', $dateft);
    $dateFrom = date('Y-m-d', strtotime($datearay[0]));
    $dateto = date('Y-m-d', strtotime($datearay[1]));
    $wherecond .= " AND (tbl_account.entry_date  BETWEEN '$dateFrom' AND '$dateto')";
}

$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;

// Count total records before filtering
$totalData = count($obj->rawSql("SELECT tbl_account.* FROM tbl_account
    LEFT JOIN tbl_agent ON tbl_agent.ag_id = tbl_account.agent_id
    LEFT JOIN _createuser ON _createuser.UserId = tbl_account.entry_by
    WHERE tbl_agent.deleted_at IS NULL $wherecond "));

// Fetch filtered records from the database
$allData = $obj->rawSql(
    "SELECT 
        tbl_account.*, 
        tbl_agent.ag_name, 
        tbl_agent.cus_id, 
        _createuser.FullName, 
        tbl_accounts_head.acc_name, 
        tbl_zone.zone_name 
    FROM 
        tbl_account 
    LEFT JOIN 
        tbl_agent 
        ON tbl_agent.ag_id = tbl_account.agent_id 
    LEFT JOIN 
        tbl_zone 
        ON tbl_zone.zone_id = tbl_agent.zone 
    LEFT JOIN 
        tbl_accounts_head 
        ON tbl_accounts_head.acc_id = tbl_account.acc_head 
    LEFT JOIN 
        _createuser 
        ON _createuser.UserId = tbl_account.entry_by 
    WHERE 
        tbl_agent.deleted_at IS NULL
        $wherecond 
    ORDER BY 
        tbl_agent.ag_id DESC
    LIMIT $length OFFSET $start;"
);


$totalFiltered = count($obj->rawSql("SELECT tbl_account.* FROM tbl_account LEFT JOIN tbl_agent ON tbl_agent.ag_id = tbl_account.agent_id WHERE tbl_agent.deleted_at IS NULL $wherecond"));



/// Initialize the totals
$debit_total = 0;
$credit_total = 0;
$balance = 0; // Ensure balance is initialized at the start
$i = 1;
$allAgentData = [];

foreach ($allData as $accounts) {
    $debit = 0;
    $credit = 0;
    $amount = $accounts['acc_amount'];  // Amount for debit/credit

    // Process debit or credit based on account type
    if ($accounts['acc_type'] == 1) {  // Assuming 1 is for debit
        $debit = $amount;
        $balance -= $debit;
        $debit_total += $debit;
    } else {  // Assuming else is for credit
        $credit = $amount;
        $balance += $credit;
        $credit_total += $credit;
    }

    // Add additional data to the row
    $accounts['sl'] = $i++;  // Serial number for row
    $accounts['debit'] = $debit;
    $accounts['acchead'] = ($accounts['agent_id'] > 0) ? $accounts['ag_name'] . '(' . $accounts['cus_id'] . ')' : $accounts['acc_name'];
    $accounts['credit'] = $credit;
    $accounts['balance'] = $balance;

    // Add the processed account data to the results
    $allAgentData[] = $accounts;
}

// Respond with JSON data for the DataTable
echo json_encode([
    "draw" => intval($_GET['draw']), // DataTable draw counter
    "recordsTotal" => $totalData,    // Total records in the database
    "recordsFiltered" => $totalFiltered, // Filtered records count
    "data" => $allAgentData,         // The actual data
    "debit_total" => $debit_total,  // Total bill calculation
    "credit_total" => $credit_total, // Total bill calculation
    "balance" => $credit_total - $debit_total,  // Total bill calculation
    "getreqdate" => $wherecond  // Total bill calculation
], JSON_PRETTY_PRINT);
exit();
?>
<!-- SELECT `ag_id`, `cus_id`, `ag_name`, `ip`, `queue_password`, `type`, `mikrotik_id`, `mikrotik_disconnect`, `taka`, `mb`, `int_mb`, `ag_status`, `ag_mobile_no`, `regular_mobile`, `ag_office_address`, `zone`, `sub_zone`, `destination`, `pay_status`, `ag_email`, `national_id`, `nationalidphoto`, `gender`, `onumac`, `fibercode`, `connectiontype`, `agent_type`, `due_status`, `bill_status`, `payment_type`, `bill_date`, `remark`, `inactive_date`, `billing_person_id`, `entry_by`, `update_by`, `entry_date`, `connection_date`, `created_at`, `last_update`, `deleted_at` FROM `tbl_agent` WHERE 1 -->