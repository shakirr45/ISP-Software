<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

// Get the year provided by the user, or default to the current year
$year = isset($_GET['dateYear']) ? intval($_GET['dateYear']) : date("Y");

// Fetch income (acc_type != 1) and expense (acc_type = 1) for the given year
$IncomeData = $obj->rawSql("
    SELECT 
        SUM(acc_amount) as amount, 
        MONTH(entry_date) as month 
    FROM tbl_account 
    WHERE YEAR(entry_date) = '$year' AND acc_type != 1
    GROUP BY MONTH(entry_date)
");

$ExpenseData = $obj->rawSql("
    SELECT 
        SUM(acc_amount) as amount, 
        MONTH(entry_date) as month 
    FROM tbl_account 
    WHERE YEAR(entry_date) = '$year' AND acc_type = 1
    GROUP BY MONTH(entry_date)
");

// Initialize arrays for storing monthly income and expense (default 0 for all months)
$incomeData = array_fill(0, 12, 0);
$expenseData = array_fill(0, 12, 0);

// Map fetched data to the corresponding months (1-12)
foreach ($IncomeData as $data) {
    $month = intval($data['month']) - 1; // Convert month to 0-based index
    $amount = floatval($data["amount"]); // Ensure numeric values
    $incomeData[$month] = $amount;
}

foreach ($ExpenseData as $data) {
    $month = intval($data['month']) - 1; // Convert month to 0-based index
    $amount = floatval($data["amount"]); // Ensure numeric values
    $expenseData[$month] = $amount;
}

// Calculate maximum value across all data points for scaling
$allData = array_merge($incomeData, $expenseData);
$maxValue = max($allData);
$maxData = $maxValue + ($maxValue * 0.5); // Add 50% margin to max value

// Return data as JSON for front-end visualization
echo json_encode([
    "incomeData" => $incomeData,   // Monthly income data
    "expenseData" => $expenseData, // Monthly expense data
    "maxData" => $maxData,         // Max data value for scaling
]);
exit();
