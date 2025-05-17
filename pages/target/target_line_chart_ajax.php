<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();


$currentYear = date("Y");
$currentMonth = date("n");



$CurrentYearUser = $obj->rawSql("SELECT COUNT(ag_id) as total ,MONTH(entry_date) as month FROM tbl_agent WHERE ag_status='1' AND MONTH(entry_date)='$currentMonth' AND YEAR(entry_date)='$currentYear' AND deleted_at IS NULL");
$currentYearTarget = $obj->rawSql("SELECT january_target,february_target,march_target,april_target,may_target,june_target,july_target,august_target,september_target,october_target,november_target,december_target FROM business_targets WHERE deleted_at IS NULL AND year='$currentYear'AND type=2 ORDER BY id DESC LIMIT 1 OFFSET 0");
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


foreach ($CurrentYearUser as $data) {
    $currentYearCollectionData[$data['month'] - 1] = $data["total"];
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
    "collection" => "New Customer",
    "target" => "Target New Customer",
    "maxData" => $maxData,
]);
exit();
