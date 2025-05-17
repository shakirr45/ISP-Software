<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

// Get the current year and month
$currentYear = date("Y");
$currentMonth = date("m");

// Fetch complaint counts along with customer details using a JOIN
$customerData = $obj->rawSql("
    SELECT 
        c.customer_id,
        a.ag_name AS name,
        COUNT(c.id) AS complain_count
    FROM tbl_complains AS c
    LEFT JOIN vw_agent AS a ON c.customer_id = a.ag_id
    WHERE YEAR(c.entry_date) = '$currentYear' AND MONTH(c.entry_date) = '$currentMonth'
    GROUP BY c.customer_id
    ORDER BY complain_count DESC
");

// Return the result as JSON
header('Content-Type: application/json');
echo json_encode([
    "customerData" => $customerData
]);
exit();
