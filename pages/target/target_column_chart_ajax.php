<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();


$currentYear = date("Y");



$CurrentYearBillCollection = $obj->rawSql("SELECT sum(acc_amount), MONTH(entry_date) AS month 
                             FROM tbl_account WHERE YEAR(entry_date)='$currentYear' AND acc_type='3' GROUP BY MONTH(entry_date)");
$currentYearTarget = $obj->rawSql("SELECT january_target,february_target,march_target,april_target,may_target,june_target,july_target,august_target,september_target,october_target,november_target,december_target FROM business_targets WHERE deleted_at IS NULL AND year='$currentYear'AND type=1 ORDER BY id DESC LIMIT 1 OFFSET 0");
// var_dump($currentYearTarget);
// exit();

// Create arrays for storing counts for each month (1-12)
$currentYearCollectionData = array_fill(0, 12, 0); // Default all months to 0
// Initialize the $currentYearTargetData array with 12 elements, all set to 0
$currentYearTargetData = array_fill(0, 12, 0);

$monthNames = [
    'january_target',
    'february_target',
    'march_target',
    'april_target',
    'may_target',
    'june_target',
    'july_target',
    'august_target',
    'september_target',
    'october_target',
    'november_target',
    'december_target'
];

// Check if $currentYearTarget[0] is set and has the required structure
if (isset($currentYearTarget[0])) {
    // Loop through each month and assign the value from $currentYearTarget to $currentYearTargetData
    foreach ($monthNames as $index => $month) {
        // Ensure the month key exists in $currentYearTarget[0]
        if (isset($currentYearTarget[0][$month])) {
            $currentYearTargetData[$index] = $currentYearTarget[0][$month];
        } else {
            echo "Key '$month' not found in \$currentYearTarget[0].\n";
        }
    }
} else {
    echo "Data structure is incorrect. Please check \$currentYearTarget.\n";
    print_r($currentYearTarget);
}


foreach ($CurrentYearBillCollection as $data) {
    $currentYearCollectionData[$data['month'] - 1] = $data["sum(acc_amount)"];
}
// Prepare data for JavaScript
$collectionData = implode(',', $currentYearCollectionData);
$targetData = implode(',', $currentYearTargetData);

// Calculate max value for the first chart
$allData = array_merge($currentYearCollectionData, $currentYearTargetData);
$maxValue = max($allData);
$maxData = $maxValue + ($maxValue * 0.50); // Add 50% margin to max value
echo json_encode([
    "collectionData" => $collectionData, // Total records after filtering
    "targetData" => $targetData,          // The actual data
    "collection" => "Bill Collections",
    "target" => "Target Bill Collection",
    "maxData" => $maxData,

]);
exit();
