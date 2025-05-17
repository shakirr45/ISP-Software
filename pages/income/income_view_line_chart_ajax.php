<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();


$currentYear = date("Y");
$previousYear = $currentYear - 1;

$PreviousYear = $obj->rawSql("SELECT SUM(acc_amount) as amount , MONTH(entry_date) as month FROM tbl_account WHERE  YEAR(entry_date)='$previousYear' AND acc_type='3' GROUP BY MONTH(entry_date)");
$CurrentYear = $obj->rawSql("SELECT SUM(acc_amount) as amount , MONTH(entry_date) as month FROM tbl_account WHERE  YEAR(entry_date)='$currentYear'  AND acc_type='3' GROUP BY MONTH(entry_date)");


// Create arrays for storing counts for each month (1-12)
$previousYearData = array_fill(0, 12, 0); // Default all months to 0
$currentYearData = array_fill(0, 12, 0); // Default all months to 0

// Map the fetched data to the corresponding months
foreach ($PreviousYear as $data) {
    $previousYearData[$data['month'] - 1] = $data["amount"];
}
foreach ($CurrentYear as $data) {
    $currentYearData[$data['month'] - 1] = $data["amount"];
}

// Prepare data for JavaScript
$previousData = implode(',', $previousYearData);
$currentData = implode(',', $currentYearData);

// Calculate max value for the first chart
$allData = array_merge($previousYearData, $currentYearData);
$maxValue = max($allData);
$maxData = $maxValue + ($maxValue * 0.5); // Add 50% margin to max value

echo json_encode([
    "previousData" => $previousData, // Total records after filtering
    "currentData" => $currentData,          // The actual data
    "currentYear" => $currentYear,          // The actual data
    "previousYear" => $previousYear,
    "maxData" => $maxData,

]);
exit();
