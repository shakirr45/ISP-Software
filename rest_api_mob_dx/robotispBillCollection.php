<?php
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();
// Create JSON array from extracted variables
$response = [
    "draw" => intval($_GET['draw']),       // Draw counter from DataTable
    "recordsTotal" => $totalData,         // Total records in database (without filters)
    "recordsFiltered" => $totalFiltered,  // Total records after filtering
    "data" => $allAgentData,              // The actual data (processed agents)
    "totalbill" => $total_bill,           // Total bill amount
    "totalconnectionFee" => $totalconnectionFee, // Total connection fee
    "totalcustomer" => $totalcustomer     // Total number of customers
];

// Encode to JSON format
$jsonResponse = json_encode($response, JSON_PRETTY_PRINT);

// Output the JSON response
echo $jsonResponse;
exit();
