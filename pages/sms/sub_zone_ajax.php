<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

// Retrieve 'zone' parameter from POST request
$zone = isset($_POST['zone']) ? $_POST['zone'] : null;

// Process the data (you can add your logic here)
$response = [
    "status" => "success",
    "received" => [
        "zone" => $zone // Return the received value
    ]
];

if (isset($_POST["zone"])) {
    $subZones = $obj->rawSql("SELECT * FROM tbl_zone WHERE parent_id = '$zone'");
}

// Send JSON response
header('Content-Type: application/json'); // Specify JSON content type
echo json_encode($subZones); // Encode the response as JSON
?>