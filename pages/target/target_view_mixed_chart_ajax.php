<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();


$currentYear = date("Y");
$currentMonth = date("n");



$CurrentYearComplain = $obj->rawSql("SELECT COUNT(id) as total ,MONTH(entry_date) as month FROM tbl_complains WHERE  YEAR(entry_date)='$currentYear' AND deleted_at IS NULL GROUP BY MONTH(entry_date)");
$currentYearTarget = $obj->rawSql("SELECT january_target,february_target,march_target,april_target,may_target,june_target,july_target,august_target,september_target,october_target,november_target,december_target FROM business_targets WHERE deleted_at IS NULL AND year='$currentYear'AND type=3 ORDER BY id DESC LIMIT 1 OFFSET 0");
// var_dump($currentYearTarget);
// exit();

// Create arrays for storing counts for each month (1-12)
$currentYearComplainData = array_fill(0, 12, 0);
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

$totalComplains = 0;

// ডেটাগুলিকে সঠিক মাসে মানচিত্র করা এবং মোট কমপ্লেইন সংখ্যা বের করা
foreach ($CurrentYearComplain as $data) {
    $monthIndex = $data['month'] - 1; // মাসের ইনডেক্স বের করা (0 থেকে 11)
    $currentYearComplainData[$monthIndex] = $data['total']; // সংশ্লিষ্ট মাসে ডেটা সেট করা
    $totalComplains += $data['total']; // মোট কমপ্লেইনের সংখ্যা যোগ করা
}

// প্রতিটি মাসের জন্য শতাংশ বের করা
$monthlyPercentageData = array_fill(0, 12, 0); // ডিফল্ট ভ্যালু 0%
if ($totalComplains > 0) { // যদি মোট কমপ্লেইন থাকে
    foreach ($currentYearComplainData as $monthIndex => $complainCount) {
        $monthlyPercentageData[$monthIndex] = round(($complainCount / $totalComplains) * 100, 2); // শতাংশ নির্ণয় (2 দশমিক পর্যন্ত)
    }
}
// Prepare data for JavaScript
$collectionData = implode(',', $monthlyPercentageData);
$targetData = implode(',', $currentYearTargetData);
$complainData = implode(',', $currentYearComplainData);

// Calculate max value for the first chart
$allData = array_merge($monthlyPercentageData, $currentYearTargetData);
$maxValue = max($allData);
$maxData = $maxValue + ($maxValue * 0.50); // Add 50% margin to max value
echo json_encode([
    "collectionData" => $collectionData, // Total records after filtering
    "targetData" => $targetData,          // The actual data
    "collection" => "Current Month Reduction",
    "target" => "Target Month Reduction",
    "maxData" => $maxData,
    'complainData' => $complainData,
]);
exit();
