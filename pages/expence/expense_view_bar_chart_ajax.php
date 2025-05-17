<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();


$currentYear = date("Y");

$incomeYear = $obj->rawSql("SELECT SUM(amount) as amount , MONTH(entry_date) as month FROM vw_all_income WHERE  YEAR(entry_date)='$currentYear' GROUP BY MONTH(entry_date)");
$expenseYear = $obj->rawSql("SELECT SUM(acc_amount) as amount , MONTH(entry_date) as month FROM tbl_account WHERE  YEAR(entry_date)='$currentYear'  AND acc_type='1' GROUP BY MONTH(entry_date)");


// Create arrays for storing counts for each month (1-12)
$incomeYearData = array_fill(0, 12, 0); // Default all months to 0
$expenseYearData = array_fill(0, 12, 0); // Default all months to 0

// Map the fetched data to the corresponding months
foreach ($incomeYear as $data) {
    $incomeYearData[$data['month'] - 1] = $data["amount"];
}
foreach ($expenseYear as $data) {
    $expenseYearData[$data['month'] - 1] = $data["amount"];
}

// Prepare data for JavaScript
$incomeData = implode(',', $incomeYearData);
$expensetData = implode(',', $expenseYearData);

// Calculate max value for the first chart
$allData = array_merge($incomeYearData, $expenseYearData);
$maxValue = max($allData);
$maxData = $maxValue + ($maxValue * 0.5); // Add 50% margin to max value

echo json_encode([
    "incomeData" => $incomeData, // Total records after filtering
    "expensetData" => $expensetData,          // The actual data
    "currentYear" => $currentYear,
    "maxData" => $maxData,

]);
exit();
