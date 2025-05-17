<?php
session_start();
require(realpath(__DIR__ . '/../../services/Model.php'));
$obj = new Model();

date_default_timezone_set('Asia/Dhaka');
$date_time = date('Y-m-d g:i:sA');
$date = date('Y-m-d');
$ip_add = $_SERVER['REMOTE_ADDR'];
$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;

$dateMonth = date("n");
$dateYear = date("Y");
$pre_dateMonth = $dateMonth - 1;
$pre_dateYear = $dateYear;

if (isset($_GET['dateMonth']) && isset($_GET['dateYear'])) {
    $dateMonth = intval($_GET['dateMonth']);
    $dateYear = intval($_GET['dateYear']);

    $pre_date = date_create("$dateYear-$dateMonth-1");
    $pre_dateMonth = date('n', strtotime("-1 month", $pre_date->getTimestamp()));
    $pre_dateYear = date('Y', strtotime("-1 month", $pre_date->getTimestamp()));
}

$all_transection_till_previous_month = $obj->get_all_income_till_previous_month($pre_dateMonth, $pre_dateYear);
$all_expense_till_previous_month = $obj->get_all_expense_till_previous_month($pre_dateMonth, $pre_dateYear);
$all_opening_till_previous_month = $obj->get_all_opening_till_previous_month($pre_dateMonth, $pre_dateYear);

$all_Income_till_previous_month = $all_opening_till_previous_month['amount'] + $all_transection_till_previous_month['amount'] - $all_expense_till_previous_month['amount'];

$totalBillCollection = 0;
$totalConnectionCharge = 0;
$totalOtherIncome = 0;
$totalOpeningAmount = 0;
$totalExpense = 0;
$totalIncome = 0;

$allAgentData = [];
$max = 31;

for ($day = 1; $day <= $max; $day++) {
    $current_date = "$dateYear-$dateMonth-$day";
    if (date('n', strtotime($current_date)) != $dateMonth) {
        continue;
    }

    $billCollection = $obj->get_dayWise_total_bill_collection($current_date);
    $connectionCharge = $obj->get_dayWise_total_connection_charge($current_date);
    $othersIncome = $obj->get_dayWise_total_other_income($current_date);
    $openingAmount = $obj->get_dayWise_total_opening_amount($current_date);
    $expense = $obj->get_daywise_total_expense($current_date);

    if ($billCollection == 0 && $connectionCharge == 0 && $othersIncome == 0 && $expense == 0 && $openingAmount == 0) {
        continue;
    }

    $totalBillCollection += $billCollection;
    $totalConnectionCharge += $connectionCharge;
    $totalOtherIncome += $othersIncome;
    $totalOpeningAmount += $openingAmount;
    $totalExpense += $expense;

    $totalIncome = $totalBillCollection + $totalConnectionCharge + $totalOtherIncome + $totalOpeningAmount;

    $cashInHand = $all_Income_till_previous_month + $totalIncome - $totalExpense;

    $allAgentData[] = [
        "date" => date('d M Y', strtotime($current_date)),
        "bill_collection" => $billCollection,
        "connection_charge" => $connectionCharge,
        "others_income" => $othersIncome,
        "opening_amounts" => $openingAmount ?? 0,
        "expense" => $expense ?? 0,
    ];
}

// Return JSON response
echo json_encode([
    "data" => $allAgentData,
    "totalOpeningAmount" => $totalOpeningAmount,
    "totalExpense" => $totalExpense,
    "totalIncome" => $totalIncome,
    "totalBillCollection" => $totalBillCollection,
    "totalConnectionCharge" => $totalConnectionCharge,
    "totalOtherIncome" => $totalOtherIncome,
    "cashInHand" => ($totalIncome + $totalOpeningAmount - $totalExpense),
    "totalAmount" => ($totalIncome + $totalOpeningAmount - $totalExpense)
]);
exit();