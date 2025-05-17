<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();




if (isset($_GET["token"])) {
    $agentInfo = $obj->rawSqlSingle("SELECT *
    FROM `tbl_agent`
    WHERE ag_id = $_GET[token]");

    $billing_info = $obj->rawSqlSingle("SELECT *
        FROM `customer_billing`
        WHERE agid = $_GET[token]");


    $accountsHistory = $obj->raw_sql("SELECT acc_id, acc_amount, acc_description,created_at, user.FullName as received_by 
                FROM `tbl_account` as account 
                LEFT JOIN _createuser as user ON user.UserId = account.entry_by
                WHERE account.agent_id = $_GET[token]");
    $totalAmount = $obj->rawSqlSingle("SELECT SUM(tbl_account.acc_amount) as totalAmount 
    FROM `tbl_account`
    WHERE agent_id = $_GET[token]");
}

$customer_billing_history = [];

foreach ($accountsHistory as $key => $history) {
    $customer_billing_history[] = [
        "sl" => $key + 1,
        "date" => $formattedDate = date("Y-m-d h:i:s A", strtotime($history["created_at"])),
        "description" => $history["acc_description"],
        "amount" => $history["acc_amount"],
        "received_by" => $history["received_by"],
        "acc_id" => $history["acc_id"],
    ];
}


// Return JSON response
echo json_encode([
    "data" => $customer_billing_history,
    "totalAmount" => $totalAmount["totalAmount"],
    "history" => $accountsHistory,
    "billing_info" => $billing_info,
    "agentInfo" => $agentInfo
]);
exit();
