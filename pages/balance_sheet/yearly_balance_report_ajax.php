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

if (isset($_GET['dateYear'])) {
    $dateYear = $_GET['dateYear'];
} else {
    $dateYear = date('Y');
}


$max = 12;
$i = 0;
$invoice = 0;
$year_total = 0;
$year_expense = 0;
$month_digit = date('n', strtotime(($date)));
$year_digit = date('Y', strtotime(($date)));
if ($year_digit == $dateYear) {
    $max = $month_digit;
}

$previous_closing_balance = 0;

$monthArray = array(
    "1" => 'January',
    "2" => 'February',
    "3" => 'March',
    "4" => 'April',
    "5" => 'May',
    "6" => 'June',
    "7" => 'July',
    "8" => 'August',
    "9" => 'September',
    "10" => 'October',
    "11" => 'November',
    "12" => 'December',
);

for ($j = 1; $j <= $max; $j++) {
    $other_totals = 0;
    $agent_totals = 0;
    $connection_totals = 0;
    $expensive_totals = 0;
    $i++;

    //Expense calculation
    $expensive_totals_data = $obj->details_selected_field_by_cond('vw_account', 'SUM(acc_amount) as total_expense', "MONTH(entry_date)='$j' and YEAR(entry_date)='$dateYear' and acc_type='1'");
    $expensive_totals = $expensive_totals_data["total_expense"];

    //Other calculate
    $other_totals_data = $obj->details_selected_field_by_cond('vw_account', 'SUM(acc_amount) as total_other_income', "MONTH(entry_date)='$j' and YEAR(entry_date)='$dateYear' and acc_type='2'");
    $other_totals = $other_totals_data['total_other_income'];


    //Agent bill calculate
    $agent_totals_data = $obj->details_selected_field_by_cond('vw_account', 'SUM(acc_amount) as total_agent_payment', "MONTH(entry_date)='$j' and YEAR(entry_date)='$dateYear' and acc_type='3'");
    $agent_totals = $agent_totals_data['total_agent_payment'];


    //Connection charge
    $connection_charge_data = $obj->details_selected_field_by_cond('tbl_account', 'SUM(acc_amount) as total_connection_charge', "acc_type = 4 AND MONTH(entry_date) = '$j' AND YEAR(entry_date) = '$dateYear'");
    $connection_totals = $connection_charge_data['total_connection_charge'];

    //opening Amount
    $opening_amount_data = $obj->details_selected_field_by_cond('vw_account', 'SUM(acc_amount) as total_opening_amount', "MONTH(entry_date)='$j' and YEAR(entry_date)='$dateYear' and acc_type='5'");
    $opening_amount = $opening_amount_data['total_opening_amount'];

    $temp_total = $other_totals + $agent_totals + $invoice + $opening_amount + $connection_totals;
    $temp_total_without_invoice = $other_totals + $agent_totals + $opening_amount + $connection_totals;

    $year_total += $temp_total_without_invoice;

    $year_expense += $expensive_totals;
    // echo $expensive_totals;

    $profit_month = ($other_totals + $agent_totals + $invoice + $opening_amount + $connection_totals) - $expensive_totals;
    $invoice = $profit_month;

    $allAgentData[] = [
        "sl" => $i,
        "month" => $monthArray[$j],
        "opening_balance" => $previous_closing_balance,
        "customer_payment" => $agent_totals ?? 0,
        "others_payment" => $other_totals ?? 0,
        "connection_charge" => $connection_totals ?? 0,
        "opening_amount" => $opening_amount ?? 0,
        "total" => $temp_total,
        "expense_statement" => $expensive_totals ?? 0,
        "closing_balance" => $profit_month
    ];

    $previous_closing_balance = $profit_month;
};

// Return JSON response for DataTable
echo json_encode([
    "data" => $allAgentData,
    "yearly_total_income" => $year_total,
    "yearly_expense" => $year_expense,
    "yearly_profit" => ($year_total - $year_expense),
]);
exit();
