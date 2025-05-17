<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

// Get the year provided by the user, or default to the current year
// $currentYear = isset($_GET['dateYear']) ? intval($_GET['dateYear']) : date("Y");
$currentYear = intval(date("Y"));
$previousYear = $currentYear - 1;

$discountStatement = $obj->rawSql("
    SELECT 
        SUM(amount) as amount, 
        MONTH(date) as month,
        YEAR(date) as year
    FROM bonus 
    WHERE YEAR(date) IN ('$currentYear', '$previousYear')
    GROUP BY YEAR(date), MONTH(date)
");

// Initialize arrays for current and previous year data with 0 for all months
$discountData = [
    "currentYear" => array_fill(0, 12, 0),
    "previousYear" => array_fill(0, 12, 0),
];

// Map the fetched data into respective arrays
foreach ($discountStatement as $income) {
    $year = intval($income['year']);
    $month = intval($income['month']) - 1;
    $amount = floatval($income['amount']);

    if ($year === $currentYear) {
        $discountData["currentYear"][$month] = $amount;
    } elseif ($year === $previousYear) {
        $discountData["previousYear"][$month] = $amount;
    }
}

// Calculate the maximum value for scaling purposes
$allData = array_merge(
    $discountData["currentYear"],
    $discountData["previousYear"],
);
$maxValue = max($allData);
$maxData = $maxValue + ($maxValue * 0.5);

// Return the data as JSON for visualization
echo json_encode([
    "discountData" => $discountData,
    "maxData" => $maxData,
    "currentYear" => $currentYear,
    "previousYear" => $previousYear
]);
exit();
