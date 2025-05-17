<?php
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

// Fetch and group data by month
$currentYear = date("Y");
$CurrentYearIncome = $obj->rawSql("SELECT SUM(acc_amount) as amount , MONTH(entry_date) as month FROM tbl_account WHERE  YEAR(entry_date)='$currentYear'  AND acc_type!='1' GROUP BY MONTH(entry_date)");
$CurrentYearExpense = $obj->rawSql("SELECT SUM(acc_amount) as amount , MONTH(entry_date) as month FROM tbl_account WHERE  YEAR(entry_date)='$currentYear'  AND acc_type='1' GROUP BY MONTH(entry_date)");
$currentYearIncomeData = array_fill(0, 12, 0);
$currentYearExpenseData = array_fill(0, 12, 0);

foreach ($CurrentYearIncome as $data) {
    $currentYearIncomeData[$data['month'] - 1] = $data["amount"];
}
foreach ($CurrentYearExpense as $data) {
    $currentYearExpenseData[$data['month'] - 1] = $data["amount"];
}

$incomeData = implode(',', $currentYearIncomeData);
$expenseData = implode(',', $currentYearExpenseData);

// Calculate max value for the first chart
$allData = array_merge($currentYearIncomeData, $currentYearExpenseData);
$maxValue = max($allData);
$maxData = $maxValue + ($maxValue * 0.5); 
// Return JSON response
echo json_encode([
    "incomeData" => $incomeData,
    "expenseData" => $expenseData,
    "maxData" => $maxData,
]);

exit();
