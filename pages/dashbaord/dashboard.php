<?php
// require(realpath(__DIR__ . '/../../services/Model.php'));
// $obj = new Model();

$currentMonth = date("M"); // Get current month in short format (e.g., Dec)
$currentYear = date("Y"); // Get full year (e.g., 2024)

$currentNumericMonth = date('n'); // Numeric month
$currentNumericYear = date('Y');  // Full numeric year

$currentMonthYear = "$currentMonth-$currentYear"; // Combine into "Dec-2024"
// Call the stored procedure FOR BILL GENERATION
// $obj->rawSqlSingle("CALL `billgeneratemonthly`();");
// Query the database
$monthBill = $obj->rawSqlSingle(
    "SELECT * 
FROM monthly_bill_making_check 
WHERE month_year = '$currentMonthYear';"
);
$bilgen = $monthBill['tbillgenerate'] ?? 0;
$due = $monthBill['tdue'] ?? 0;
$totalDue = $bilgen + $due;

$totalBill = $obj->rawSqlSingle(
    "SELECT SUM(tbillgenerate) AS total FROM monthly_bill_making_check"
);

// Current Month Collection

$get_all_collection = $obj->get_all_income_with_condition($currentNumericMonth, $currentNumericYear, 'AND (acc_type=2 OR acc_type=3 OR acc_type=4 OR acc_type=5 )');

// print_r($get_all_collection);

$get_connection_charge = $obj->get_all_income_with_condition($currentNumericMonth, $currentNumericYear, 'AND  acc_type=4');

// var_dump($get_all_collection);
// exit();
