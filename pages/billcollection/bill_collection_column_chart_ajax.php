<?php 
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();


$currentYear = date("Y");
$previousYear = $currentYear - 1;

// Fetch data for the first chart (Bill Amount per month)
// $PreviousYear = $obj->rawSql("SELECT sum(taka), MONTH(entry_date) AS month 
//                               FROM vw_agent WHERE YEAR(entry_date)='$previousYear' GROUP BY MONTH(entry_date)");

$CurrentYearBillCollection = $obj->rawSql("SELECT sum(taka), MONTH(entry_date) AS month 
                             FROM vw_agent WHERE YEAR(entry_date)='$currentYear' GROUP BY MONTH(entry_date)");

$CurrentYearExpense = $obj->rawSql("SELECT sum(acc_amount), MONTH(entry_date) AS month 
                             FROM tbl_account WHERE YEAR(entry_date)='$currentYear' AND acc_type='1' GROUP BY MONTH(entry_date)");

// Create arrays for storing counts for each month (1-12)
$currentYearCollectionData = array_fill(0, 12, 0); // Default all months to 0
$currentYearExpenseData = array_fill(0, 12, 0); // Default all months to 0

// Map the fetched data to the corresponding months
// foreach ($PreviousYear as $data) {
//     $previousYearData[$data['month'] - 1] = $data["sum(taka)"];
// }
foreach ($CurrentYearBillCollection as $data) {
    $currentYearCollectionData[$data['month'] - 1] = $data["sum(taka)"];
}

foreach ($CurrentYearExpense as $data) {
    $currentYearExpenseData[$data['month'] - 1] = $data["sum(acc_amount)"];
}

// Prepare data for JavaScript
$collectionData = implode(',', $currentYearCollectionData);
$expenseData = implode(',', $currentYearExpenseData);

// Calculate max value for the first chart
$allData = array_merge($currentYearCollectionData, $currentYearExpenseData);
$maxValue = max($allData);
$maxData = $maxValue + ($maxValue * 0.50); // Add 50% margin to max value
echo json_encode([
    "collectionData" => $collectionData, // Total records after filtering
    "expenseData" => $expenseData,          // The actual data
    "collection" => "Collections",          
    "expense" => "Expenses",
    "maxData" => $maxData,

]);
exit();
?>