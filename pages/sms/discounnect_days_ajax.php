<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

// Retrieve 'zone' parameter from POST request
$subZone = isset($_POST['subZone']) ? $_POST['subZone'] : null;

// Process the data (you can add your logic here)
$response = [
    "status" => "success",
    "received" => [
        "subZone" => $subZone // Return the received value
    ]
];

if (isset($_POST["subZone"])) {
    $disconnectDays = $obj->rawSql("SELECT DISTINCT mikrotik_disconnect FROM tbl_agent WHERE sub_zone = '$subZone' ORDER BY mikrotik_disconnect ASC");
}

// Send JSON response
header('Content-Type: application/json'); // Specify JSON content type
echo json_encode($disconnectDays); // Encode the response as JSON
?>