<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

// Get the year provided by the user, or default to the current year
$currentYear = isset($_GET['dateYear']) ? intval($_GET['dateYear']) : date("Y");
$previousYear = $currentYear - 1;

// Combined query for income and expense
$incomeStatement = $obj->rawSql("
    SELECT 
        SUM(acc_amount) as amount, 
        MONTH(entry_date) as month,
        YEAR(entry_date) as year
    FROM tbl_account 
    WHERE YEAR(entry_date) IN ('$currentYear', '$previousYear') AND acc_type != 1
    GROUP BY YEAR(entry_date), MONTH(entry_date)
");

$expenseStatement = $obj->rawSql("
    SELECT 
        SUM(acc_amount) as amount, 
        MONTH(entry_date) as month,
        YEAR(entry_date) as year
    FROM tbl_account 
    WHERE YEAR(entry_date) IN ('$currentYear', '$previousYear') AND acc_type = 1
    GROUP BY YEAR(entry_date), MONTH(entry_date)
");

// Initialize arrays for current and previous year data with 0 for all months
$incomeData = [
    "currentYear" => array_fill(0, 12, 0),
    "previousYear" => array_fill(0, 12, 0),
];

$expenseData = [
    "currentYear" => array_fill(0, 12, 0),
    "previousYear" => array_fill(0, 12, 0),
];

// Map the fetched data into respective arrays
foreach ($incomeStatement as $income) {
    $year = intval($income['year']);
    $month = intval($income['month']) - 1;
    $amount = floatval($income['amount']);

    if ($year === $currentYear) {
        $incomeData["currentYear"][$month] = $amount;
    } elseif ($year === $previousYear) {
        $incomeData["previousYear"][$month] = $amount;
    }
}

foreach ($expenseStatement as $expense) {
    $year = intval($expense['year']);
    $month = intval($expense['month']) - 1;
    $amount = floatval($expense['amount']);

    if ($year === $currentYear) {
        $expenseData["currentYear"][$month] = $amount;
    } elseif ($year === $previousYear) {
        $expenseData["previousYear"][$month] = $amount;
    }
}

// Calculate the maximum value for scaling purposes
$allData = array_merge(
    $incomeData["currentYear"],
    $incomeData["previousYear"],
    $expenseData["currentYear"],
    $expenseData["previousYear"]
);
$maxValue = max($allData);
$maxData = $maxValue + ($maxValue * 0.5); // Add 50% margin to max value

// Return the data as JSON for visualization
echo json_encode([
    "incomeData" => $incomeData,
    "expenseData" => $expenseData,
    "maxData" => $maxData
]);
exit();
