<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

// Retrieve 'zone' parameter from POST request
$disconnectDay = isset($_POST['disconnectDay']) ? $_POST['disconnectDay'] : null;

$response = [
    "status" => "success",
    "received" => [
        "disconnectDay" => $disconnectDay
    ]
];

if (isset($_POST["disconnectDay"])) {
    $billingPersons = $obj->rawSql("SELECT DISTINCT tbl_agent.entry_by, user.UserName 
    FROM tbl_agent 
    LEFT JOIN _createuser AS user ON user.UserId = tbl_agent.entry_by
    WHERE mikrotik_disconnect = '$disconnectDay' ORDER BY entry_by ASC");
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($billingPersons);
?>