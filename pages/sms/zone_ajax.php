<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

// Retrieve 'package' parameter from POST request
$package = isset($_POST['package']) ? $_POST['package'] : null;

$response = [
    "status" => "success",
    "received" => [
        "package" => $package
    ]
];

if (isset($_POST["package"])) {
    $zone = $obj->rawSql("SELECT DISTINCT tbl_zone.zone_id, tbl_zone.zone_name FROM tbl_agent 
    LEFT JOIN tbl_zone ON tbl_zone.zone_id = tbl_agent.zone
    WHERE mb = '$package'");
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($zone);
?>