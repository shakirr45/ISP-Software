
<?php 
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();


$currentYear = date("Y");
$previousYear = $currentYear - 1;

// Fetch data for the first chart (Agent counts per month)
$PreviousYear = $obj->rawSql("select count(acc_id), MONTH(entry_date) as month from tbl_account where YEAR(entry_date)='$previousYear' and acc_type = 1 group by MONTH(entry_date)");
$CurrentYear = $obj->rawSql("select count(acc_id), MONTH(entry_date) as month from tbl_account where YEAR(entry_date)='$currentYear' and acc_type = 1 group by MONTH(entry_date)");

// Create arrays to store the counts for each month (1-12)
$previousData = array_fill(0, 12, 0); // Default all months to 0
$currentData = array_fill(0, 12, 0); // Default all months to 0

// Map the fetched data to the corresponding months (index 0 represents January)
foreach ($PreviousYear as $data) {
    $previousData[$data['month'] - 1] = $data["count(acc_id)"];
}
foreach ($CurrentYear as $data) {
    $currentData[$data['month'] - 1] = $data["count(acc_id)"];
}


// Combine all data for calculating max value
$allData = array_merge($previousData, $currentData);
$maxValue = max($allData);
$maxData = $maxValue + ($maxValue * 0.50); // Add 50% margin to max value

echo json_encode([
    "previousData" => $previousData, // Total records after filtering
    "currentData" => $currentData,          // The actual data
    "currentYear" => $currentYear,          // The actual data
    "previousYear" => $previousYear,
    "maxData" => $maxData,

]);
exit();
?>
