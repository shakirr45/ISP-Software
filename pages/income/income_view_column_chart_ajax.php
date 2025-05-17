<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();


$currentYear = date("Y");
$paidAgents = [];
$unpaidAgents = [];
$partiallyPaidAgents = [];

// Fetch data for the first chart (Agent counts per month)


$result = $obj->rawSql("SELECT 
    MONTH(entry_date) AS month, 
    COUNT(CASE WHEN due_status = 1 AND pay_status = 1 THEN ag_id END) AS partially_paid_agents, 
    COUNT(CASE WHEN due_status = 0 AND pay_status = 1 THEN ag_id END) AS paid_agents,
    COUNT(CASE WHEN due_status = 0 AND pay_status = 1 THEN ag_id END) AS unpaid_agents 
FROM 
    tbl_agent
WHERE 
    YEAR(entry_date) = '$currentYear' 
    AND ag_status = '1'
GROUP BY 
    MONTH(entry_date)
");

// Initialize the arrays for paid, unpaid, and partially paid agents
$paidAgents = array_fill(0, 12, 0); // Default all months to 0
$unpaidAgents = array_fill(0, 12, 0); // Default all months to 0
$partiallyPaidAgents = array_fill(0, 12, 0); // Default all months to 0

// Loop through the result and map the fetched data to the corresponding months
foreach ($result as $data) {
    // Correctly assign the values to the specific month index (0-based)
    $month = $data['month'] - 1; // Adjust for zero-based index

    $paidAgents[$month] = $data['paid_agents'];
    $unpaidAgents[$month] = $data['unpaid_agents'];
    $partiallyPaidAgents[$month] = $data['partially_paid_agents'];
}

$paidAgentsData = implode(',', $paidAgents);
$unpaidAgentsData = implode(',', $unpaidAgents);
$partiallyPaidAgentsData = implode(',', $partiallyPaidAgents);

// Prepare the response data for JSON
echo json_encode([
    "paidAgents" => $paidAgentsData,
    "unpaidAgents" => $unpaidAgentsData,
    "partiallyPaidAgents" => $partiallyPaidAgentsData,
    "currentYear" => $currentYear,
]);

// Prepare data for JavaScript
// $previousData = implode(',', $previousYearData);


// Calculate max value for the first chart
// $maxValue = max($currentData);
// $maxData = $maxValue + ($maxValue * 0.50); // Add 50% margin to max value

// echo json_encode([
//     "currentData" => $currentData,          // The actual data
//     "currentYear" => $currentYear,          // The actual data
//     "maxData" => $maxData,
// ]);
exit();
