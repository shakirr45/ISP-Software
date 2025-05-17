<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$dateMonth = date("n");
$dateYear = date("Y");

if (isset($_GET['dateYear'])) {
    $dateYear = intval($_GET['dateYear']);
    $previousYear = $dateYear - 1;
} else {
    $dateYear = intval($dateYear);
    $previousYear = $dateYear - 1;
}

$PreviousYear = $obj->rawSql("SELECT SUM(acc_amount) as amount , MONTH(entry_date) as month FROM tbl_account WHERE  YEAR(entry_date)='$previousYear' AND acc_type!='1' GROUP BY MONTH(entry_date)");
$CurrentYear = $obj->rawSql("SELECT SUM(acc_amount) as amount , MONTH(entry_date) as month FROM tbl_account WHERE  YEAR(entry_date)='$dateYear'  AND acc_type!='1' GROUP BY MONTH(entry_date)");


// Create arrays for storing counts for each month (1-12)
$previousYearData = array_fill(0, 12, 0);
$currentYearData = array_fill(0, 12, 0);

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
$maxData = $maxValue + ($maxValue * 0.5);


echo json_encode([
    "previousData" => $previousData,
    "currentData" => $currentData,
    "currentYearLabel" => $dateYear,
    "previousYearLabel" => $previousYear,
    "maxData" => $maxData,
]);

exit();
