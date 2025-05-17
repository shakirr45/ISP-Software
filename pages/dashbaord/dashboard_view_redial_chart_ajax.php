<?php

session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

$currentMonth = date("M"); // Get current month in short format (e.g., Dec)
$currentYear = date("Y"); // Get full year (e.g., 2024)
$currentNumericMonth = date('n'); // Numeric month
$currentNumericYear = date('Y');  // Full numeric year

$currentMonthYear = "$currentMonth-$currentYear"; // Combine into "Dec-2024"

// Query the database
$monthBill = $obj->rawSqlSingle(
    "SELECT * 
FROM monthly_bill_making_check 
WHERE month_year = '$currentMonthYear';"
);

$totalBiil = $monthBill['tbillgenerate'] ?? 0;

$getBillCollection = $obj->get_all_income_with_condition($currentNumericMonth, $currentNumericYear, 'AND  acc_type=3');

$totalBiilCollection = $getBillCollection['amount'] ?? 0;

echo json_encode([
    "totalBill" => $totalBiil ?? 0,
    "totalBillCollection" => $totalBiilCollection ?? 0,
]);
